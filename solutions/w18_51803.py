// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/security/ReentrancyGuard.sol";
import "@openzeppelin/contracts/utils/cryptography/ECDSA.sol";

contract UnifiedSharing is Ownable, ReentrancyGuard {
    using ECDSA for bytes32;

    // Structs
    struct ShareRequest {
        address requester;
        address token;
        uint256 amount;
        uint256 expiry;
        bool executed;
    }

    struct AppRegistration {
        string appId;
        address appAddress;
        bool active;
        uint256 registrationTime;
    }

    // State variables
    mapping(bytes32 => ShareRequest) public shareRequests;
    mapping(string => AppRegistration) public registeredApps;
    mapping(address => mapping(string => bool)) public userAppPermissions;
    
    uint256 public constant MAX_SHARE_AMOUNT = 1000000 * 10**18; // 1M tokens max
    uint256 public constant SHARE_EXPIRY = 24 hours;
    uint256 public constant MIN_SHARE_AMOUNT = 1 * 10**18; // 1 token minimum

    // Events
    event ShareCreated(bytes32 indexed shareId, address indexed requester, address token, uint256 amount);
    event ShareExecuted(bytes32 indexed shareId, address indexed executor, uint256 amount);
    event ShareCancelled(bytes32 indexed shareId);
    event AppRegistered(string indexed appId, address appAddress);
    event AppUnregistered(string indexed appId);
    event UserPermissionGranted(address indexed user, string indexed appId);
    event UserPermissionRevoked(address indexed user, string indexed appId);

    // Modifiers
    modifier validShare(bytes32 shareId) {
        require(shareRequests[shareId].requester != address(0), "Share does not exist");
        require(!shareRequests[shareId].executed, "Share already executed");
        require(block.timestamp <= shareRequests[shareId].expiry, "Share expired");
        _;
    }

    modifier onlyRegisteredApp(string memory appId) {
        require(registeredApps[appId].active, "App not registered");
        require(registeredApps[appId].appAddress == msg.sender, "Not authorized app");
        _;
    }

    modifier hasUserPermission(address user, string memory appId) {
        require(userAppPermissions[user][appId], "User permission not granted");
        _;
    }

    // Constructor
    constructor() {
        // Initialize with owner
    }

    // App Registration Functions
    function registerApp(string memory appId, address appAddress) external onlyOwner {
        require(bytes(appId).length > 0, "App ID cannot be empty");
        require(appAddress != address(0), "Invalid app address");
        require(!registeredApps[appId].active, "App already registered");

        registeredApps[appId] = AppRegistration({
            appId: appId,
            appAddress: appAddress,
            active: true,
            registrationTime: block.timestamp
        });

        emit AppRegistered(appId, appAddress);
    }

    function unregisterApp(string memory appId) external onlyOwner {
        require(registeredApps[appId].active, "App not registered");
        registeredApps[appId].active = false;
        emit AppUnregistered(appId);
    }

    // User Permission Management
    function grantPermission(string memory appId) external {
        require(registeredApps[appId].active, "App not registered");
        userAppPermissions[msg.sender][appId] = true;
        emit UserPermissionGranted(msg.sender, appId);
    }

    function revokePermission(string memory appId) external {
        require(userAppPermissions[msg.sender][appId], "Permission not granted");
        userAppPermissions[msg.sender][appId] = false;
        emit UserPermissionRevoked(msg.sender, appId);
    }

    // Core Sharing Functions
    function createShare(
        address token,
        uint256 amount,
        bytes32 shareId
    ) external nonReentrant returns (bytes32) {
        require(token != address(0), "Invalid token address");
        require(amount >= MIN_SHARE_AMOUNT && amount <= MAX_SHARE_AMOUNT, "Invalid amount");
        require(shareId != bytes32(0), "Invalid share ID");
        require(shareRequests[shareId].requester == address(0), "Share ID already exists");

        // Transfer tokens to contract
        IERC20(token).transferFrom(msg.sender, address(this), amount);

        shareRequests[shareId] = ShareRequest({
            requester: msg.sender,
            token: token,
            amount: amount,
            expiry: block.timestamp + SHARE_EXPIRY,
            executed: false
        });

        emit ShareCreated(shareId, msg.sender, token, amount);
        return shareId;
    }

    function executeShare(
        bytes32 shareId,
        address recipient,
        string memory appId
    ) external nonReentrant validShare(shareId) onlyRegisteredApp(appId) hasUserPermission(recipient, appId) {
        ShareRequest storage request = shareRequests[shareId];
        require(request.requester != recipient, "Cannot share with yourself");

        request.executed = true;
        
        // Transfer tokens to recipient
        IERC20(request.token).transfer(recipient, request.amount);

        emit ShareExecuted(shareId, recipient, request.amount);
    }

    function cancelShare(bytes32 shareId) external nonReentrant validShare(shareId) {
        ShareRequest storage request = shareRequests[shareId];
        require(request.requester == msg.sender, "Not the requester");

        request.executed = true;
        
        // Return tokens to requester
        IERC20(request.token).transfer(request.requester, request.amount);

        emit ShareCancelled(shareId);
    }

    // Cross-App Sharing
    function crossAppShare(
        bytes32 shareId,
        address recipient,
        string memory sourceAppId,
        string memory targetAppId
    ) external nonReentrant validShare(shareId) onlyRegisteredApp(sourceAppId) onlyRegisteredApp(targetAppId) {
        ShareRequest storage request = shareRequests[shareId];
        require(request.requester == msg.sender, "Not the requester");
        require(recipient != address(0), "Invalid recipient");
        require(keccak256(bytes(sourceAppId)) != keccak256(bytes(targetAppId)), "Same app");

        // Verify cross-app permissions
        require(userAppPermissions[recipient][sourceAppId], "Source app permission not granted");
        require(userAppPermissions[recipient][targetAppId], "Target app permission not granted");

        request.executed = true;
        
        // Transfer tokens to recipient
        IERC20(request.token).transfer(recipient, request.amount);

        emit ShareExecuted(shareId, recipient, request.amount);
    }

    // Batch Operations
    function batchCreateShares(
        address[] memory tokens,
        uint256[] memory amounts,
        bytes32[] memory shareIds
    ) external nonReentrant {
        require(tokens.length == amounts.length && amounts.length == shareIds.length, "Array length mismatch");
        
        for (uint256 i = 0; i < tokens.length; i++) {
            createShare(tokens[i], amounts[i], shareIds[i]);
        }
    }

    // Utility Functions
    function getShareDetails(bytes32 shareId) external view returns (
        address requester,
        address token,
        uint256 amount,
        uint256 expiry,
        bool executed
    ) {
        ShareRequest storage request = shareRequests[shareId];
        return (
            request.requester,
            request.token,
            request.amount,
            request.expiry,
            request.executed
        );
    }

    function isShareValid(bytes32 shareId) external view returns (bool) {
        ShareRequest storage request = shareRequests[shareId];
        return request.requester != address(0) && 
               !request.executed && 
               block.timestamp <= request.expiry;
    }

    // Emergency Functions
    function emergencyWithdraw(address token, uint256 amount) external onlyOwner {
        IERC20(token).transfer(owner(), amount);
    }

    // Receive function to accept native currency (if needed)
    receive() external payable {
        // Accept native currency
    }
}
