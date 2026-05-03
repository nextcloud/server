// src/controllers/UserManagementController.js
const User = require('../models/User');
const { validationResult } = require('express-validator');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');

class UserManagementController {
  // Get all users with pagination and filtering
  async getAllUsers(req, res) {
    try {
      const page = parseInt(req.query.page) || 1;
      const limit = parseInt(req.query.limit) || 10;
      const skip = (page - 1) * limit;
      const search = req.query.search || '';
      const role = req.query.role || '';

      let query = {};
      if (search) {
        query.$or = [
          { username: { $regex: search, $options: 'i' } },
          { email: { $regex: search, $options: 'i' } },
          { displayName: { $regex: search, $options: 'i' } }
        ];
      }
      if (role) {
        query.role = role;
      }

      const users = await User.find(query)
        .select('-password')
        .skip(skip)
        .limit(limit)
        .sort({ createdAt: -1 });

      const total = await User.countDocuments(query);

      res.json({
        success: true,
        data: {
          users,
          pagination: {
            page,
            limit,
            total,
            pages: Math.ceil(total / limit)
          }
        }
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        error: 'Failed to fetch users',
        message: error.message
      });
    }
  }

  // Get single user by ID
  async getUserById(req, res) {
    try {
      const user = await User.findById(req.params.id).select('-password');
      if (!user) {
        return res.status(404).json({
          success: false,
          error: 'User not found'
        });
      }
      res.json({
        success: true,
        data: user
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        error: 'Failed to fetch user',
        message: error.message
      });
    }
  }

  // Create new user
  async createUser(req, res) {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({
          success: false,
          errors: errors.array()
        });
      }

      const { username, email, password, displayName, role } = req.body;

      // Check if user already exists
      const existingUser = await User.findOne({
        $or: [{ email }, { username }]
      });

      if (existingUser) {
        return res.status(400).json({
          success: false,
          error: 'User with this email or username already exists'
        });
      }

      // Hash password
      const salt = await bcrypt.genSalt(10);
      const hashedPassword = await bcrypt.hash(password, salt);

      const user = new User({
        username,
        email,
        password: hashedPassword,
        displayName,
        role: role || 'user',
        status: 'active',
        lastLogin: null,
        createdAt: new Date(),
        updatedAt: new Date()
      });

      await user.save();

      // Generate JWT token
      const token = jwt.sign(
        { userId: user._id, role: user.role },
        process.env.JWT_SECRET,
        { expiresIn: '24h' }
      );

      res.status(201).json({
        success: true,
        data: {
          user: {
            _id: user._id,
            username: user.username,
            email: user.email,
            displayName: user.displayName,
            role: user.role,
            status: user.status,
            createdAt: user.createdAt
          },
          token
        }
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        error: 'Failed to create user',
        message: error.message
      });
    }
  }

  // Update user
  async updateUser(req, res) {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({
          success: false,
          errors: errors.array()
        });
      }

      const { displayName, email, role, status } = req.body;
      const userId = req.params.id;

      // Check if user exists
      const user = await User.findById(userId);
      if (!user) {
        return res.status(404).json({
          success: false,
          error: 'User not found'
        });
      }

      // Check if email is already taken by another user
      if (email && email !== user.email) {
        const emailExists = await User.findOne({ email, _id: { $ne: userId } });
        if (emailExists) {
          return res.status(400).json({
            success: false,
            error: 'Email already in use'
          });
        }
      }

      const updateData = {
        updatedAt: new Date()
      };

      if (displayName) updateData.displayName = displayName;
      if (email) updateData.email = email;
      if (role) updateData.role = role;
      if (status) updateData.status = status;

      const updatedUser = await User.findByIdAndUpdate(
        userId,
        { $set: updateData },
        { new: true, runValidators: true }
      ).select('-password');

      res.json({
        success: true,
        data: updatedUser,
        message: 'User updated successfully'
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        error: 'Failed to update user',
        message: error.message
      });
    }
  }

  // Delete user
  async deleteUser(req, res) {
    try {
      const userId = req.params.id;

      // Prevent deleting yourself
      if (userId === req.user.userId) {
        return res.status(400).json({
          success: false,
          error: 'Cannot delete your own account'
        });
      }

      const user = await User.findByIdAndDelete(userId);
      if (!user) {
        return res.status(404).json({
          success: false,
          error: 'User not found'
        });
      }

      res.json({
        success: true,
        message: 'User deleted successfully'
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        error: 'Failed to delete user',
        message: error.message
      });
    }
  }

  // Change user password
  async changePassword(req, res) {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({
          success: false,
          errors: errors.array()
        });
      }

      const { currentPassword, newPassword } = req.body;
      const userId = req.params.id;

      const user = await User.findById(userId);
      if (!user) {
        return res.status(404).json({
          success: false,
          error: 'User not found'
        });
      }

      // Verify current password
      const isMatch = await bcrypt.compare(currentPassword, user.password);
      if (!isMatch) {
        return res.status(400).json({
          success: false,
          error: 'Current password is incorrect'
        });
      }

      // Hash new password
      const salt = await bcrypt.genSalt(10);
      const hashedPassword = await bcrypt.hash(newPassword, salt);

      user.password = hashedPassword;
      user.updatedAt = new Date();
      await user.save();

      res.json({
        success: true,
        message: 'Password changed successfully'
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        error: 'Failed to change password',
        message: error.message
      });
    }
  }

  // Update user status (activate/deactivate)
  async updateUserStatus(req, res) {
    try {
      const { status } = req.body;
      const userId = req.params.id;

      if (!['active', 'inactive', 'suspended'].includes(status)) {
        return res.status(400).json({
          success: false,
          error: 'Invalid status value'
        });
      }

      const user = await User.findByIdAndUpdate(
        userId,
        { 
          $set: { 
            status, 
            updatedAt: new Date() 
          } 
        },
        { new: true }
      ).select('-password');

      if (!user) {
        return res.status(404).json({
          success: false,
          error: 'User not found'
        });
      }

      res.json({
        success: true,
        data: user,
        message: `User status updated to ${status}`
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        error: 'Failed to update user status',
        message: error.message
      });
    }
  }

  // Bulk user operations
  async bulkOperation(req, res) {
    try {
      const { userIds, operation } = req.body;

      if (!Array.isArray(userIds) || userIds.length === 0) {
        return res.status(400).json({
          success: false,
          error: 'Invalid user IDs array'
        });
      }

      let result;
      switch (operation) {
        case 'delete':
          result = await User.deleteMany({ _id: { $in: userIds } });
          break;
        case 'activate':
          result = await User.updateMany(
            { _id: { $in: userIds } },
            { $set: { status: 'active', updatedAt: new Date() } }
          );
          break;
        case 'deactivate':
          result = await User.updateMany(
            { _id: { $in: userIds } },
            { $set: { status: 'inactive', updatedAt: new Date() } }
          );
          break;
        default:
          return res.status(400).json({
            success: false,
            error: 'Invalid operation'
          });
      }

      res.json({
        success: true,
        data: {
          modifiedCount: result.modifiedCount || result.deletedCount,
          operation
        },
        message: `Successfully performed ${operation} on ${result.modifiedCount || result.deletedCount} users`
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        error: 'Failed to perform bulk operation',
        message: error.message
      });
    }
  }

  // Get user statistics
  async getUserStats(req, res) {
    try {
      const stats = await User.aggregate([
        {
          $group: {
            _id: null,
            totalUsers: { $sum: 1 },
            activeUsers: {
              $sum: { $cond: [{ $eq: ['$status', 'active'] }, 1, 0] }
            },
            inactiveUsers: {
              $sum: { $cond: [{ $eq: ['$status', 'inactive'] }, 1, 0] }
            },
            suspendedUsers: {
              $sum: { $cond: [{ $eq: ['$status', 'suspended'] }, 1, 0] }
            },
            adminUsers: {
              $sum: { $cond: [{ $eq: ['$role', 'admin'] }, 1, 0] }
            },
            regularUsers: {
              $sum: { $cond: [{ $eq: ['$role', 'user'] }, 1, 0] }
            }
          }
        }
      ]);

      res.json({
        success: true,
        data: stats[0] || {
          totalUsers: 0,
          activeUsers: 0,
          inactiveUsers: 0,
          suspendedUsers: 0,
          adminUsers: 0,
          regularUsers: 0
        }
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        error: 'Failed to get user statistics',
        message: error.message
      });
    }
  }
}

module.exports = new UserManagementController();
