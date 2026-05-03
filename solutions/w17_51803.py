// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/security/ReentrancyGuard.sol";

contract UnifiedSharing is Ownable, ReentrancyGuard {
    // Mapping from app ID to app configuration
    mapping(bytes32 => AppConfig) public apps;
    
    // Mapping from user to their shared content
    mapping(address => UserShares) public userShares;
    
    // Events
    event AppRegistered(bytes32 indexed appId, string appName, address appOwner);
    event ContentShared(bytes32 indexed appId, address indexed sharer, bytes32 contentId, uint256 timestamp);
    event RewardDistributed(bytes32 indexed appId, address indexed sharer, uint256 amount);
    
    struct AppConfig {
        string appName;
        address appOwner;
        bool isActive;
        uint256 rewardPool;
        uint256 rewardPerShare;
        uint256 totalShares;
    }
    
    struct UserShares {
        mapping(bytes32 => ShareDetails) shares;
        bytes32[] shareIds;
        uint256 totalRewards;
    }
    
    struct ShareDetails {
        bytes32 appId;
        bytes32 contentId;
        uint256 timestamp;
        bool rewarded;
    }
    
    // Platform fee (0.5%)
    uint256 public constant PLATFORM_FEE = 50;
    uint256 public constant FEE_DENOMINATOR = 10000;
    
    // Supported apps tracking
    bytes32[] public registeredApps;
    
    constructor() {}
    
    // Register a new app for sharing
    function registerApp(
        bytes32 appId,
        string calldata appName,
        uint256 initialRewardPool
    ) external payable returns (bool) {
        require(apps[appId].appOwner == address(0), "App already registered");
        require(msg.value >= initialRewardPool, "Insufficient initial pool");
        
        apps[appId] = AppConfig({
            appName: appName,
            appOwner: msg.sender,
            isActive: true,
            rewardPool: initialRewardPool,
            rewardPerShare: 0,
            totalShares: 0
        });
        
        registeredApps.push(appId);
        
        emit AppRegistered(appId, appName, msg.sender);
        return true;
    }
    
    // Share content from a supported app
    function shareContent(
        bytes32 appId,
        bytes32 contentId
    ) external nonReentrant returns (bool) {
        AppConfig storage app = apps[appId];
        require(app.isActive, "App not active");
        require(app.appOwner != address(0), "App not registered");
        
        UserShares storage user = userShares[msg.sender];
        require(user.shares[contentId].timestamp == 0, "Already shared");
        
        // Record the share
        user.shares[contentId] = ShareDetails({
            appId: appId,
            contentId: contentId,
            timestamp: block.timestamp,
            rewarded: false
        });
        user.shareIds.push(contentId);
        
        // Update app stats
        app.totalShares++;
        
        emit ContentShared(appId, msg.sender, contentId, block.timestamp);
        return true;
    }
    
    // Distribute rewards for a share
    function distributeReward(
        bytes32 appId,
        address sharer,
        bytes32 contentId
    ) external nonReentrant returns (bool) {
        AppConfig storage app = apps[appId];
        require(app.isActive, "App not active");
        require(msg.sender == app.appOwner, "Only app owner can distribute");
        
        UserShares storage user = userShares[sharer];
        ShareDetails storage share = user.shares[contentId];
        require(share.timestamp > 0, "Share not found");
        require(!share.rewarded, "Already rewarded");
        
        uint256 reward = app.rewardPerShare > 0 ? app.rewardPerShare : 0.001 ether;
        require(app.rewardPool >= reward, "Insufficient reward pool");
        
        // Calculate platform fee
        uint256 fee = (reward * PLATFORM_FEE) / FEE_DENOMINATOR;
        uint256 netReward = reward - fee;
        
        // Update state
        share.rewarded = true;
        app.rewardPool -= reward;
        user.totalRewards += netReward;
        
        // Transfer rewards
        payable(sharer).transfer(netReward);
        payable(owner()).transfer(fee);
        
        emit RewardDistributed(appId, sharer, netReward);
        return true;
    }
    
    // Add funds to reward pool
    function addToRewardPool(bytes32 appId) external payable {
        AppConfig storage app = apps[appId];
        require(app.appOwner == msg.sender, "Only app owner");
        app.rewardPool += msg.value;
    }
    
    // Get user's shared content for a specific app
    function getUserShares(address user, bytes32 appId) 
        external 
        view 
        returns (bytes32[] memory, uint256[] memory) 
    {
        UserShares storage userShare = userShares[user];
        uint256 count = 0;
        
        // Count shares for this app
        for (uint256 i = 0; i < userShare.shareIds.length; i++) {
            if (userShare.shares[userShare.shareIds[i]].appId == appId) {
                count++;
            }
        }
        
        bytes32[] memory contentIds = new bytes32[](count);
        uint256[] memory timestamps = new uint256[](count);
        uint256 index = 0;
        
        for (uint256 i = 0; i < userShare.shareIds.length; i++) {
            if (userShare.shares[userShare.shareIds[i]].appId == appId) {
                contentIds[index] = userShare.shareIds[i];
                timestamps[index] = userShare.shares[userShare.shareIds[i]].timestamp;
                index++;
            }
        }
        
        return (contentIds, timestamps);
    }
    
    // Get all registered apps
    function getRegisteredApps() external view returns (bytes32[] memory) {
        return registeredApps;
    }
    
    // Deactivate an app
    function deactivateApp(bytes32 appId) external {
        AppConfig storage app = apps[appId];
        require(app.appOwner == msg.sender || msg.sender == owner(), "Not authorized");
        app.isActive = false;
    }
    
    // Reactivate an app
    function reactivateApp(bytes32 appId) external {
        AppConfig storage app = apps[appId];
        require(app.appOwner == msg.sender || msg.sender == owner(), "Not authorized");
        app.isActive = true;
    }
    
    // Withdraw remaining pool (only app owner)
    function withdrawPool(bytes32 appId) external nonReentrant {
        AppConfig storage app = apps[appId];
        require(app.appOwner == msg.sender, "Only app owner");
        require(!app.isActive, "App must be deactivated");
        
        uint256 amount = app.rewardPool;
        app.rewardPool = 0;
        payable(msg.sender).transfer(amount);
    }
}
