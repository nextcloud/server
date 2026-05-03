// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/security/ReentrancyGuard.sol";

contract UnifiedSharing is Ownable, ReentrancyGuard {
    // Mapping from app ID to app contract address
    mapping(bytes32 => address) public appContracts;
    
    // Mapping from user to their shared content across apps
    mapping(address => mapping(bytes32 => ContentShare[])) public userShares;
    
    // Mapping from content hash to its metadata
    mapping(bytes32 => ContentMetadata) public contentRegistry;
    
    struct ContentShare {
        bytes32 contentHash;
        address sharer;
        uint256 timestamp;
        bytes32 appId;
        string permission;
        bool active;
    }
    
    struct ContentMetadata {
        address creator;
        uint256 createdAt;
        uint256 accessCount;
        mapping(address => bool) authorizedUsers;
    }
    
    // Events
    event ContentShared(address indexed sharer, bytes32 indexed contentHash, bytes32 appId);
    event ContentUnshared(address indexed sharer, bytes32 indexed contentHash, bytes32 appId);
    event AppRegistered(bytes32 appId, address appContract);
    event AccessGranted(address indexed user, bytes32 indexed contentHash);
    
    // Fee structure for cross-app sharing
    uint256 public constant SHARING_FEE = 0.001 ether;
    address public feeCollector;
    
    constructor() {
        feeCollector = msg.sender;
    }
    
    // Register a new app for unified sharing
    function registerApp(bytes32 appId, address appContract) external onlyOwner {
        require(appContracts[appId] == address(0), "App already registered");
        appContracts[appId] = appContract;
        emit AppRegistered(appId, appContract);
    }
    
    // Share content across apps
    function shareContent(
        bytes32 contentHash,
        bytes32 targetAppId,
        string memory permission,
        address[] memory authorizedUsers
    ) external payable nonReentrant {
        require(appContracts[targetAppId] != address(0), "Target app not registered");
        require(msg.value >= SHARING_FEE, "Insufficient sharing fee");
        
        // Create content metadata if not exists
        if (contentRegistry[contentHash].creator == address(0)) {
            contentRegistry[contentHash].creator = msg.sender;
            contentRegistry[contentHash].createdAt = block.timestamp;
        }
        
        // Add authorized users
        for (uint256 i = 0; i < authorizedUsers.length; i++) {
            contentRegistry[contentHash].authorizedUsers[authorizedUsers[i]] = true;
            emit AccessGranted(authorizedUsers[i], contentHash);
        }
        
        // Record the share
        ContentShare memory newShare = ContentShare({
            contentHash: contentHash,
            sharer: msg.sender,
            timestamp: block.timestamp,
            appId: targetAppId,
            permission: permission,
            active: true
        });
        
        userShares[msg.sender][targetAppId].push(newShare);
        
        // Transfer fee
        payable(feeCollector).transfer(SHARING_FEE);
        
        emit ContentShared(msg.sender, contentHash, targetAppId);
    }
    
    // Unshare content
    function unshareContent(bytes32 contentHash, bytes32 appId) external {
        ContentShare[] storage shares = userShares[msg.sender][appId];
        for (uint256 i = 0; i < shares.length; i++) {
            if (shares[i].contentHash == contentHash && shares[i].active) {
                shares[i].active = false;
                emit ContentUnshared(msg.sender, contentHash, appId);
                return;
            }
        }
        revert("Share not found");
    }
    
    // Get user's shares for a specific app
    function getUserShares(address user, bytes32 appId) 
        external 
        view 
        returns (ContentShare[] memory) 
    {
        return userShares[user][appId];
    }
    
    // Check if user has access to content
    function hasAccess(address user, bytes32 contentHash) external view returns (bool) {
        return contentRegistry[contentHash].authorizedUsers[user] || 
               contentRegistry[contentHash].creator == user;
    }
    
    // Get content creator
    function getContentCreator(bytes32 contentHash) external view returns (address) {
        return contentRegistry[contentHash].creator;
    }
    
    // Update fee collector
    function updateFeeCollector(address newCollector) external onlyOwner {
        require(newCollector != address(0), "Invalid address");
        feeCollector = newCollector;
    }
    
    // Withdraw accumulated fees
    function withdrawFees() external onlyOwner {
        payable(owner()).transfer(address(this).balance);
    }
    
    // Fallback function to receive ETH
    receive() external payable {}
}
