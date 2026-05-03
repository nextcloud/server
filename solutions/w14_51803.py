// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/security/ReentrancyGuard.sol";

contract UnifiedSharing is Ownable, ReentrancyGuard {
    // Mapping from app identifier to its sharing configuration
    mapping(bytes32 => AppSharingConfig) public appConfigs;
    
    // Mapping from user to their shared content across apps
    mapping(address => UserShares) public userShares;
    
    // Events
    event ContentShared(address indexed user, bytes32 indexed appId, bytes32 contentHash, uint256 timestamp);
    event AccessGranted(address indexed user, bytes32 indexed appId, address indexed grantee, bytes32 contentHash);
    event AccessRevoked(address indexed user, bytes32 indexed appId, address indexed grantee, bytes32 contentHash);
    event AppRegistered(bytes32 indexed appId, string appName, address appContract);
    
    struct AppSharingConfig {
        string appName;
        address appContract;
        bool isActive;
        uint256 maxShareSize;
        uint256 registrationTime;
    }
    
    struct UserShares {
        mapping(bytes32 => SharedContent[]) appShares;
        mapping(bytes32 => mapping(address => mapping(bytes32 => bool))) accessPermissions;
    }
    
    struct SharedContent {
        bytes32 contentHash;
        string metadata;
        uint256 timestamp;
        bool isActive;
    }
    
    // Platform fee configuration
    uint256 public platformFee = 0; // 0% fee for now
    address public feeCollector;
    
    constructor() {
        feeCollector = msg.sender;
    }
    
    // Register a new app for unified sharing
    function registerApp(
        bytes32 appId,
        string calldata appName,
        address appContract,
        uint256 maxShareSize
    ) external onlyOwner {
        require(appConfigs[appId].registrationTime == 0, "App already registered");
        require(appContract != address(0), "Invalid contract address");
        
        appConfigs[appId] = AppSharingConfig({
            appName: appName,
            appContract: appContract,
            isActive: true,
            maxShareSize: maxShareSize,
            registrationTime: block.timestamp
        });
        
        emit AppRegistered(appId, appName, appContract);
    }
    
    // Share content across supported apps
    function shareContent(
        bytes32 appId,
        bytes32 contentHash,
        string calldata metadata
    ) external payable nonReentrant {
        require(appConfigs[appId].isActive, "App not active");
        require(bytes(metadata).length <= appConfigs[appId].maxShareSize, "Metadata too large");
        
        // Check if content already shared
        SharedContent[] storage shares = userShares[msg.sender].appShares[appId];
        for (uint256 i = 0; i < shares.length; i++) {
            require(shares[i].contentHash != contentHash, "Content already shared");
        }
        
        // Create new share
        shares.push(SharedContent({
            contentHash: contentHash,
            metadata: metadata,
            timestamp: block.timestamp,
            isActive: true
        }));
        
        // Handle platform fee if applicable
        if (platformFee > 0 && msg.value > 0) {
            uint256 fee = (msg.value * platformFee) / 10000;
            payable(feeCollector).transfer(fee);
        }
        
        emit ContentShared(msg.sender, appId, contentHash, block.timestamp);
    }
    
    // Grant access to specific content for another user
    function grantAccess(
        bytes32 appId,
        address grantee,
        bytes32 contentHash
    ) external {
        require(grantee != address(0), "Invalid grantee address");
        require(grantee != msg.sender, "Cannot grant access to self");
        
        // Verify user owns the content
        SharedContent[] storage shares = userShares[msg.sender].appShares[appId];
        bool found = false;
        for (uint256 i = 0; i < shares.length; i++) {
            if (shares[i].contentHash == contentHash && shares[i].isActive) {
                found = true;
                break;
            }
        }
        require(found, "Content not found or inactive");
        
        // Grant access
        userShares[msg.sender].accessPermissions[appId][grantee][contentHash] = true;
        
        emit AccessGranted(msg.sender, appId, grantee, contentHash);
    }
    
    // Revoke access from a user
    function revokeAccess(
        bytes32 appId,
        address grantee,
        bytes32 contentHash
    ) external {
        require(userShares[msg.sender].accessPermissions[appId][grantee][contentHash], "Access not granted");
        
        userShares[msg.sender].accessPermissions[appId][grantee][contentHash] = false;
        
        emit AccessRevoked(msg.sender, appId, grantee, contentHash);
    }
    
    // Check if a user has access to specific content
    function hasAccess(
        address owner,
        bytes32 appId,
        address user,
        bytes32 contentHash
    ) external view returns (bool) {
        // Owner always has access
        if (owner == user) return true;
        
        return userShares[owner].accessPermissions[appId][user][contentHash];
    }
    
    // Get all shared content for a user in a specific app
    function getUserShares(
        address user,
        bytes32 appId
    ) external view returns (SharedContent[] memory) {
        return userShares[user].appShares[appId];
    }
    
    // Deactivate a share
    function deactivateShare(bytes32 appId, bytes32 contentHash) external {
        SharedContent[] storage shares = userShares[msg.sender].appShares[appId];
        for (uint256 i = 0; i < shares.length; i++) {
            if (shares[i].contentHash == contentHash) {
                shares[i].isActive = false;
                break;
            }
        }
    }
    
    // Update platform fee (owner only)
    function updatePlatformFee(uint256 newFee) external onlyOwner {
        require(newFee <= 1000, "Fee too high"); // Max 10%
        platformFee = newFee;
    }
    
    // Update fee collector
    function updateFeeCollector(address newCollector) external onlyOwner {
        require(newCollector != address(0), "Invalid address");
        feeCollector = newCollector;
    }
    
    // Toggle app active status
    function toggleAppStatus(bytes32 appId) external onlyOwner {
        appConfigs[appId].isActive = !appConfigs[appId].isActive;
    }
    
    // Withdraw accumulated fees
    function withdrawFees() external onlyOwner {
        uint256 balance = address(this).balance;
        require(balance > 0, "No fees to withdraw");
        payable(owner()).transfer(balance);
    }
    
    // Receive function to accept ETH
    receive() external payable {}
}
