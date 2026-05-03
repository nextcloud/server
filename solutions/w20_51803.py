// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/security/ReentrancyGuard.sol";

contract UnifiedSharing is Ownable, ReentrancyGuard {
    // Mapping to track supported apps and their sharing status
    mapping(address => bool) public supportedApps;
    mapping(address => mapping(address => uint256)) public appBalances;
    
    // Event for sharing actions
    event Shared(address indexed from, address indexed to, uint256 amount, address app);
    event AppSupported(address indexed app, bool supported);
    
    constructor() {
        // Initialize with some default supported apps (can be extended)
        supportedApps[address(0x1)] = true; // Example app 1
        supportedApps[address(0x2)] = true; // Example app 2
    }
    
    // Modifier to check if app is supported
    modifier onlySupportedApp(address app) {
        require(supportedApps[app], "App not supported");
        _;
    }
    
    // Function to support/un-support an app (only owner)
    function setAppSupport(address app, bool supported) external onlyOwner {
        supportedApps[app] = supported;
        emit AppSupported(app, supported);
    }
    
    // Core sharing function
    function share(address to, uint256 amount, address app) external nonReentrant onlySupportedApp(app) {
        require(to != address(0), "Invalid recipient");
        require(amount > 0, "Amount must be greater than 0");
        require(appBalances[msg.sender][app] >= amount, "Insufficient balance");
        
        // Transfer balance from sender to recipient within the app
        appBalances[msg.sender][app] -= amount;
        appBalances[to][app] += amount;
        
        emit Shared(msg.sender, to, amount, app);
    }
    
    // Function to deposit tokens into the sharing system
    function deposit(uint256 amount, address app, address token) external nonReentrant onlySupportedApp(app) {
        require(amount > 0, "Amount must be greater than 0");
        require(token != address(0), "Invalid token address");
        
        IERC20(token).transferFrom(msg.sender, address(this), amount);
        appBalances[msg.sender][app] += amount;
    }
    
    // Function to withdraw tokens from the sharing system
    function withdraw(uint256 amount, address app, address token) external nonReentrant onlySupportedApp(app) {
        require(amount > 0, "Amount must be greater than 0");
        require(appBalances[msg.sender][app] >= amount, "Insufficient balance");
        
        appBalances[msg.sender][app] -= amount;
        IERC20(token).transfer(msg.sender, amount);
    }
    
    // View function to check balance
    function getBalance(address user, address app) external view returns (uint256) {
        return appBalances[user][app];
    }
    
    // Emergency function to recover stuck tokens
    function recoverTokens(address token, uint256 amount) external onlyOwner {
        IERC20(token).transfer(owner(), amount);
    }
}
