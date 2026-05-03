// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract ExpensiveRepairFix {
    address public owner;
    mapping(address => uint256) public repairCount;
    mapping(address => uint256) public lastRepairTime;
    uint256 public constant REPAIR_COOLDOWN = 1 days;
    uint256 public constant MAX_REPAIRS_PER_DAY = 3;
    uint256 public constant REPAIR_COST = 0.01 ether;
    
    event RepairCompleted(address indexed user, uint256 timestamp);
    event RepairFailed(address indexed user, string reason);
    
    modifier onlyOwner() {
        require(msg.sender == owner, "Not owner");
        _;
    }
    
    constructor() {
        owner = msg.sender;
    }
    
    function repair() external payable {
        require(msg.value >= REPAIR_COST, "Insufficient payment");
        require(block.timestamp >= lastRepairTime[msg.sender] + REPAIR_COOLDOWN, "Cooldown active");
        require(repairCount[msg.sender] < MAX_REPAIRS_PER_DAY, "Daily limit reached");
        
        // Reset repair count if new day
        if (block.timestamp >= lastRepairTime[msg.sender] + 1 days) {
            repairCount[msg.sender] = 0;
        }
        
        repairCount[msg.sender]++;
        lastRepairTime[msg.sender] = block.timestamp;
        
        emit RepairCompleted(msg.sender, block.timestamp);
    }
    
    function withdraw() external onlyOwner {
        payable(owner).transfer(address(this).balance);
    }
    
    function getRepairInfo(address user) external view returns (uint256 count, uint256 lastTime, bool canRepair) {
        count = repairCount[user];
        lastTime = lastRepairTime[user];
        canRepair = block.timestamp >= lastTime + REPAIR_COOLDOWN && count < MAX_REPAIRS_PER_DAY;
    }
}
