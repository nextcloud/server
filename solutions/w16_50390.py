// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract ExpensiveRepairFix {
    // Mapping to track repair status
    mapping(address => bool) public hasRepairPending;
    mapping(address => uint256) public repairCost;
    
    // Events
    event RepairRequested(address indexed user, uint256 cost);
    event RepairCompleted(address indexed user, uint256 cost);
    
    // Owner
    address public owner;
    
    // Modifier
    modifier onlyOwner() {
        require(msg.sender == owner, "Not owner");
        _;
    }
    
    constructor() {
        owner = msg.sender;
    }
    
    // Request repair with reduced cost
    function requestRepair() external payable {
        require(!hasRepairPending[msg.sender], "Repair already pending");
        require(msg.value >= 0.001 ether, "Minimum repair cost: 0.001 ETH");
        
        hasRepairPending[msg.sender] = true;
        repairCost[msg.sender] = msg.value;
        
        emit RepairRequested(msg.sender, msg.value);
    }
    
    // Complete repair (owner only)
    function completeRepair(address user) external onlyOwner {
        require(hasRepairPending[user], "No repair pending");
        
        uint256 cost = repairCost[user];
        hasRepairPending[user] = false;
        repairCost[user] = 0;
        
        // Refund excess payment
        if (cost > 0.001 ether) {
            uint256 refund = cost - 0.001 ether;
            payable(user).transfer(refund);
        }
        
        emit RepairCompleted(user, cost);
    }
    
    // Withdraw funds (owner only)
    function withdraw() external onlyOwner {
        uint256 balance = address(this).balance;
        require(balance > 0, "No funds to withdraw");
        
        payable(owner).transfer(balance);
    }
    
    // Check repair status
    function checkRepairStatus(address user) external view returns (bool, uint256) {
        return (hasRepairPending[user], repairCost[user]);
    }
}
