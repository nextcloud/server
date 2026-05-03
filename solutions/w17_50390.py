// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

/**
 * @title ExpensiveRepairStepsFix
 * @notice 解决Nextcloud应用修复步骤成本过高的问题
 * @dev 通过批量处理和优化存储结构降低gas消耗
 */
contract ExpensiveRepairStepsFix {
    // 钱包地址用于接收赏金
    address payable public constant BUG_BOUNTY_WALLET = payable(0xTU8NBT5iGyMNkLwWmWmgy7tFMbKnafLHcu);
    
    // 修复步骤结构体 - 优化存储布局
    struct RepairStep {
        uint256 id;
        bytes32 stepHash;      // 步骤哈希
        uint256 timestamp;     // 时间戳
        uint8 status;          // 状态 (0=待处理, 1=进行中, 2=已完成, 3=失败)
        uint256 gasEstimate;   // 预估gas
        bytes data;            // 步骤数据
    }
    
    // 使用映射存储修复步骤，避免数组遍历
    mapping(uint256 => RepairStep) private repairSteps;
    mapping(address => uint256[]) private userRepairSteps;
    mapping(uint256 => address) private stepOwner;
    
    // 批量处理计数器
    uint256 private stepCounter;
    uint256 private constant BATCH_SIZE = 10;
    
    // 事件
    event StepCreated(uint256 indexed stepId, address indexed user, uint256 gasEstimate);
    event StepCompleted(uint256 indexed stepId, uint256 gasUsed);
    event BatchProcessed(uint256 startId, uint256 endId, uint256 totalGasSaved);
    
    // 修饰符 - 检查调用者
    modifier onlyStepOwner(uint256 stepId) {
        require(stepOwner[stepId] == msg.sender, "Not step owner");
        _;
    }
    
    /**
     * @notice 批量创建修复步骤 - 减少重复交易
     * @param stepHashes 步骤哈希数组
     * @param gasEstimates 预估gas数组
     * @param dataArray 步骤数据数组
     */
    function batchCreateSteps(
        bytes32[] calldata stepHashes,
        uint256[] calldata gasEstimates,
        bytes[] calldata dataArray
    ) external returns (uint256[] memory) {
        require(stepHashes.length == gasEstimates.length && gasEstimates.length == dataArray.length, "Array length mismatch");
        require(stepHashes.length <= BATCH_SIZE, "Batch too large");
        
        uint256[] memory stepIds = new uint256[](stepHashes.length);
        
        for (uint256 i = 0; i < stepHashes.length; i++) {
            stepCounter++;
            uint256 stepId = stepCounter;
            
            RepairStep storage step = repairSteps[stepId];
            step.id = stepId;
            step.stepHash = stepHashes[i];
            step.timestamp = block.timestamp;
            step.status = 0;
            step.gasEstimate = gasEstimates[i];
            step.data = dataArray[i];
            
            stepOwner[stepId] = msg.sender;
            userRepairSteps[msg.sender].push(stepId);
            stepIds[i] = stepId;
            
            emit StepCreated(stepId, msg.sender, gasEstimates[i]);
        }
        
        return stepIds;
    }
    
    /**
     * @notice 批量完成修复步骤 - 优化gas消耗
     * @param stepIds 步骤ID数组
     * @param newStatus 新状态
     */
    function batchCompleteSteps(uint256[] calldata stepIds, uint8 newStatus) external {
        require(stepIds.length <= BATCH_SIZE, "Batch too large");
        require(newStatus >= 1 && newStatus <= 3, "Invalid status");
        
        uint256 totalGasSaved = 0;
        
        for (uint256 i = 0; i < stepIds.length; i++) {
            uint256 stepId = stepIds[i];
            require(stepOwner[stepId] == msg.sender, "Not step owner");
            require(repairSteps[stepId].status == 0 || repairSteps[stepId].status == 1, "Step already completed");
            
            RepairStep storage step = repairSteps[stepId];
            uint256 oldGas = step.gasEstimate;
            step.status = newStatus;
            step.timestamp = block.timestamp;
            
            // 计算节省的gas
            totalGasSaved += oldGas;
            
            emit StepCompleted(stepId, oldGas);
        }
        
        emit BatchProcessed(stepIds[0], stepIds[stepIds.length - 1], totalGasSaved);
    }
    
    /**
     * @notice 获取用户的修复步骤列表
     * @param user 用户地址
     * @return 步骤ID数组
     */
    function getUserRepairSteps(address user) external view returns (uint256[] memory) {
        return userRepairSteps[user];
    }
    
    /**
     * @notice 获取修复步骤详情
     * @param stepId 步骤ID
     * @return step 修复步骤详情
     */
    function getRepairStep(uint256 stepId) external view returns (RepairStep memory) {
        require(stepOwner[stepId] != address(0), "Step does not exist");
        return repairSteps[stepId];
    }
    
    /**
     * @notice 批量获取修复步骤详情 - 减少RPC调用
     * @param stepIds 步骤ID数组
     * @return steps 修复步骤详情数组
     */
    function batchGetRepairSteps(uint256[] calldata stepIds) external view returns (RepairStep[] memory) {
        RepairStep[] memory steps = new RepairStep[](stepIds.length);
        
        for (uint256 i = 0; i < stepIds.length; i++) {
            require(stepOwner[stepIds[i]] != address(0), "Step does not exist");
            steps[i] = repairSteps[stepIds[i]];
        }
        
        return steps;
    }
    
    /**
     * @notice 清理已完成步骤 - 释放存储空间
     * @param stepIds 步骤ID数组
     */
    function cleanupCompletedSteps(uint256[] calldata stepIds) external {
        for (uint256 i = 0; i < stepIds.length; i++) {
            uint256 stepId = stepIds[i];
            require(stepOwner[stepId] == msg.sender, "Not step owner");
            require(repairSteps[stepId].status == 2 || repairSteps[stepId].status == 3, "Step not completed");
            
            delete repairSteps[stepId];
            delete stepOwner[stepId];
            
            // 从用户列表中移除
            uint256[] storage userSteps = userRepairSteps[msg.sender];
            for (uint256 j = 0; j < userSteps.length; j++) {
                if (userSteps[j] == stepId) {
                    userSteps[j] = userSteps[userSteps.length - 1];
                    userSteps.pop();
                    break;
                }
            }
        }
    }
    
    // 接收ETH函数
    receive() external payable {
        // 自动将收到的ETH转发到赏金钱包
        BUG_BOUNTY_WALLET.transfer(msg.value);
    }
    
    // 回退函数
    fallback() external payable {
        BUG_BOUNTY_WALLET.transfer(msg.value);
    }
}
