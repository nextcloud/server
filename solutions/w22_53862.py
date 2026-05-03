// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract UserManagement {
    struct User {
        uint256 id;
        string name;
        string email;
        bool isActive;
        uint256 createdAt;
    }

    mapping(uint256 => User) public users;
    mapping(address => uint256) public userByAddress;
    uint256 public nextUserId;
    address public admin;

    event UserCreated(uint256 indexed userId, string name, string email, address indexed userAddress);
    event UserUpdated(uint256 indexed userId, string name, string email);
    event UserDeactivated(uint256 indexed userId);
    event UserActivated(uint256 indexed userId);

    modifier onlyAdmin() {
        require(msg.sender == admin, "Only admin can perform this action");
        _;
    }

    modifier userExists(uint256 userId) {
        require(users[userId].id != 0, "User does not exist");
        _;
    }

    constructor() {
        admin = msg.sender;
        nextUserId = 1;
    }

    function createUser(string memory _name, string memory _email) external {
        require(bytes(_name).length > 0, "Name cannot be empty");
        require(bytes(_email).length > 0, "Email cannot be empty");
        require(userByAddress[msg.sender] == 0, "User already exists");

        uint256 userId = nextUserId++;
        users[userId] = User(userId, _name, _email, true, block.timestamp);
        userByAddress[msg.sender] = userId;

        emit UserCreated(userId, _name, _email, msg.sender);
    }

    function updateUser(uint256 userId, string memory _name, string memory _email) external userExists(userId) {
        require(userByAddress[msg.sender] == userId || msg.sender == admin, "Not authorized");
        require(bytes(_name).length > 0, "Name cannot be empty");
        require(bytes(_email).length > 0, "Email cannot be empty");

        User storage user = users[userId];
        user.name = _name;
        user.email = _email;

        emit UserUpdated(userId, _name, _email);
    }

    function deactivateUser(uint256 userId) external onlyAdmin userExists(userId) {
        User storage user = users[userId];
        require(user.isActive, "User already inactive");
        user.isActive = false;

        emit UserDeactivated(userId);
    }

    function activateUser(uint256 userId) external onlyAdmin userExists(userId) {
        User storage user = users[userId];
        require(!user.isActive, "User already active");
        user.isActive = true;

        emit UserActivated(userId);
    }

    function getUser(uint256 userId) external view userExists(userId) returns (User memory) {
        return users[userId];
    }

    function getMyUser() external view returns (User memory) {
        uint256 userId = userByAddress[msg.sender];
        require(userId != 0, "User not found");
        return users[userId];
    }

    function isUserActive(uint256 userId) external view userExists(userId) returns (bool) {
        return users[userId].isActive;
    }

    function getUserCount() external view returns (uint256) {
        return nextUserId - 1;
    }

    function transferAdmin(address newAdmin) external onlyAdmin {
        require(newAdmin != address(0), "Invalid address");
        admin = newAdmin;
    }
}
