// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract ExpensiveRepairSteps {
    address public owner;
    mapping(address => uint256) public repairCosts;
    mapping(address => bool) public isRepairPending;
    mapping(address => uint256) public pendingRepairCost;
    
    event RepairRequested(address indexed app, uint256 cost);
    event RepairCompleted(address indexed app, uint256 cost);
    event PaymentReceived(address indexed from, uint256 amount);
    
    modifier onlyOwner() {
        require(msg.sender == owner, "Only owner can call this function");
        _;
    }
    
    constructor() {
        owner = msg.sender;
    }
    
    function requestRepair(address app, uint256 cost) external onlyOwner {
        require(!isRepairPending[app], "Repair already pending");
        require(cost > 0, "Cost must be greater than 0");
        
        isRepairPending[app] = true;
        pendingRepairCost[app] = cost;
        repairCosts[app] = cost;
        
        emit RepairRequested(app, cost);
    }
    
    function completeRepair(address app) external onlyOwner {
        require(isRepairPending[app], "No pending repair");
        require(address(this).balance >= pendingRepairCost[app], "Insufficient balance");
        
        uint256 cost = pendingRepairCost[app];
        isRepairPending[app] = false;
        pendingRepairCost[app] = 0;
        
        payable(owner).transfer(cost);
        
        emit RepairCompleted(app, cost);
    }
    
    function payForRepair(address app) external payable {
        require(isRepairPending[app], "No pending repair");
        require(msg.value == pendingRepairCost[app], "Incorrect payment amount");
        
        emit PaymentReceived(msg.sender, msg.value);
    }
    
    function getRepairCost(address app) external view returns (uint256) {
        return repairCosts[app];
    }
    
    function getPendingRepairCost(address app) external view returns (uint256) {
        return pendingRepairCost[app];
    }
    
    function isRepairPendingForApp(address app) external view returns (bool) {
        return isRepairPending[app];
    }
    
    function withdrawFunds() external onlyOwner {
        payable(owner).transfer(address(this).balance);
    }
    
    receive() external payable {
        emit PaymentReceived(msg.sender, msg.value);
    }
}
