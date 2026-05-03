// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract ExpensiveRepairSteps {
    address public owner;
    mapping(address => uint256) public balances;
    mapping(address => bool) public isRepairing;
    uint256 public constant REPAIR_COST = 0.1 ether;
    uint256 public constant REPAIR_STEPS = 5;

    event RepairStarted(address indexed user, uint256 timestamp);
    event RepairStepCompleted(address indexed user, uint256 step);
    event RepairCompleted(address indexed user, uint256 totalCost);

    modifier onlyOwner() {
        require(msg.sender == owner, "Not owner");
        _;
    }

    constructor() {
        owner = msg.sender;
    }

    function startRepair() external payable {
        require(msg.value >= REPAIR_COST, "Insufficient payment");
        require(!isRepairing[msg.sender], "Already repairing");
        
        isRepairing[msg.sender] = true;
        balances[msg.sender] = msg.value;
        
        emit RepairStarted(msg.sender, block.timestamp);
    }

    function performRepairStep() external {
        require(isRepairing[msg.sender], "Not in repair");
        require(balances[msg.sender] >= REPAIR_COST / REPAIR_STEPS, "Insufficient balance");
        
        // Simulate expensive repair step
        uint256 stepCost = REPAIR_COST / REPAIR_STEPS;
        balances[msg.sender] -= stepCost;
        
        emit RepairStepCompleted(msg.sender, block.timestamp);
    }

    function completeRepair() external {
        require(isRepairing[msg.sender], "Not in repair");
        require(balances[msg.sender] == 0, "Steps not completed");
        
        isRepairing[msg.sender] = false;
        
        emit RepairCompleted(msg.sender, REPAIR_COST);
    }

    function withdrawFunds() external onlyOwner {
        payable(owner).transfer(address(this).balance);
    }

    function getRepairStatus(address user) external view returns (bool, uint256) {
        return (isRepairing[user], balances[user]);
    }
}
