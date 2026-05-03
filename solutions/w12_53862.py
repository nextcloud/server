// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract UserManagement {
    struct User {
        string username;
        string email;
        string role; // "admin", "user", "moderator"
        bool isActive;
        uint256 createdAt;
        uint256 updatedAt;
    }

    address public owner;
    mapping(address => User) public users;
    address[] public userAddresses;

    event UserCreated(address indexed userAddress, string username, string email, string role);
    event UserUpdated(address indexed userAddress, string username, string email, string role);
    event UserDeactivated(address indexed userAddress);
    event UserActivated(address indexed userAddress);

    modifier onlyOwner() {
        require(msg.sender == owner, "Only owner can perform this action");
        _;
    }

    modifier userExists(address _userAddress) {
        require(bytes(users[_userAddress].username).length > 0, "User does not exist");
        _;
    }

    constructor() {
        owner = msg.sender;
    }

    function createUser(address _userAddress, string memory _username, string memory _email, string memory _role) public onlyOwner {
        require(bytes(users[_userAddress].username).length == 0, "User already exists");
        require(bytes(_username).length > 0, "Username cannot be empty");
        require(bytes(_email).length > 0, "Email cannot be empty");
        require(isValidRole(_role), "Invalid role");

        users[_userAddress] = User({
            username: _username,
            email: _email,
            role: _role,
            isActive: true,
            createdAt: block.timestamp,
            updatedAt: block.timestamp
        });

        userAddresses.push(_userAddress);
        emit UserCreated(_userAddress, _username, _email, _role);
    }

    function updateUser(address _userAddress, string memory _username, string memory _email, string memory _role) public onlyOwner userExists(_userAddress) {
        require(bytes(_username).length > 0, "Username cannot be empty");
        require(bytes(_email).length > 0, "Email cannot be empty");
        require(isValidRole(_role), "Invalid role");

        users[_userAddress].username = _username;
        users[_userAddress].email = _email;
        users[_userAddress].role = _role;
        users[_userAddress].updatedAt = block.timestamp;

        emit UserUpdated(_userAddress, _username, _email, _role);
    }

    function deactivateUser(address _userAddress) public onlyOwner userExists(_userAddress) {
        require(users[_userAddress].isActive, "User is already deactivated");
        users[_userAddress].isActive = false;
        users[_userAddress].updatedAt = block.timestamp;
        emit UserDeactivated(_userAddress);
    }

    function activateUser(address _userAddress) public onlyOwner userExists(_userAddress) {
        require(!users[_userAddress].isActive, "User is already active");
        users[_userAddress].isActive = true;
        users[_userAddress].updatedAt = block.timestamp;
        emit UserActivated(_userAddress);
    }

    function getUser(address _userAddress) public view returns (string memory, string memory, string memory, bool, uint256, uint256) {
        require(bytes(users[_userAddress].username).length > 0, "User does not exist");
        User memory user = users[_userAddress];
        return (user.username, user.email, user.role, user.isActive, user.createdAt, user.updatedAt);
    }

    function getAllUsers() public view returns (address[] memory) {
        return userAddresses;
    }

    function getUserCount() public view returns (uint256) {
        return userAddresses.length;
    }

    function isValidRole(string memory _role) private pure returns (bool) {
        return (keccak256(abi.encodePacked(_role)) == keccak256(abi.encodePacked("admin")) ||
                keccak256(abi.encodePacked(_role)) == keccak256(abi.encodePacked("user")) ||
                keccak256(abi.encodePacked(_role)) == keccak256(abi.encodePacked("moderator")));
    }
}
