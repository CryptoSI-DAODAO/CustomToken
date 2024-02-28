// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

import "@openzeppelin/contracts/token/ERC20/ERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/security/Pausable.sol";
import "@openzeppelin/contracts/utils/structs/EnumerableSet.sol";

contract CryptosiTest is ERC20, Ownable, Pausable {
    using EnumerableSet for EnumerableSet.AddressSet;

    address public constant BURN_ADDRESS = 0x000000000000000000000000000000000000dEaD;
    address public constant DAO_TREASURY = 0x1e2f1d01538897fbd2Ce10AdDb54035f9C0DA980;
    address public constant FIRST_WHITELISTED_ADDRESS = 0x5024FE2320Fa6aC6a9209199A0dFDa6b94bd2FdF;

    uint256 public constant INITIAL_SUPPLY = 100000000000 * (10 ** 18);
    uint256 public constant TRANSFER_FEE_RATE = 50; // 5%

    mapping(address => bool) public signers;
    mapping(address => bool) public whitelisted;
    EnumerableSet.AddressSet private whitelistPendingAdditions;
    EnumerableSet.AddressSet private whitelistPendingRemovals;

    event WhitelistAdditionRequested(address indexed account);
    event WhitelistRemovalRequested(address indexed account);
    event WhitelistAdditionConfirmed(address indexed account);
    event WhitelistRemovalConfirmed(address indexed account);
    event SignerAdded(address indexed signer);
    event SignerRemoved(address indexed signer);

    modifier onlySigner() {
        require(signers[msg.sender], "Caller is not a signer");
        _;
    }

    constructor() ERC20("CryptosiTest", "CRDT") Ownable(msg.sender) {
        _mint(msg.sender, INITIAL_SUPPLY);
        whitelisted[FIRST_WHITELISTED_ADDRESS] = true;
        signers[msg.sender] = true;
        signers[0xB97351f9019486aB1F75c8F4062fdcBD39D12F2E] = true;
    }

    function transfer(address recipient, uint256 amount) public virtual override whenNotPaused returns (bool) {
        uint256 fee = calculateFee(msg.sender, recipient, amount);
        uint256 transferAmount = amount - fee;

        _transfer(msg.sender, BURN_ADDRESS, fee / 2);
        _transfer(msg.sender, DAO_TREASURY, fee / 2);
        _transfer(msg.sender, recipient, transferAmount);

        return true;
    }

    function calculateFee(address sender, address recipient, uint256 amount) internal view returns (uint256) {
        if (sender == owner() || whitelisted[recipient] || whitelisted[sender]) {
            return 0; // No fee if transaction is from owner to whitelisted address
        }
        return (amount * TRANSFER_FEE_RATE) / 1000; // Calculate transfer fee
    }

    function requestAddToWhitelist(address account) external onlySigner whenNotPaused {
        require(!whitelisted[account], "Address already whitelisted");
        require(!whitelistPendingRemovals.contains(account), "Address removal pending");

        whitelistPendingAdditions.add(account);
        emit WhitelistAdditionRequested(account);
    }

    function confirmAddToWhitelist(address account) external onlySigner whenNotPaused {
        require(whitelistPendingAdditions.contains(account), "Address not requested for addition");
        
        whitelisted[account] = true;
        whitelistPendingAdditions.remove(account);
        emit WhitelistAdditionConfirmed(account);
    }

    function requestRemoveFromWhitelist(address account) external onlySigner whenNotPaused {
        require(whitelisted[account], "Address not whitelisted");
        require(!whitelistPendingAdditions.contains(account), "Address addition pending");

        whitelistPendingRemovals.add(account);
        emit WhitelistRemovalRequested(account);
    }

    function confirmRemoveFromWhitelist(address account) external onlySigner whenNotPaused {
        require(whitelistPendingRemovals.contains(account), "Address not requested for removal");
        
        whitelisted[account] = false;
        whitelistPendingRemovals.remove(account);
        emit WhitelistRemovalConfirmed(account);
    }

    function isWhitelistAdditionPending(address account) external view returns (bool) {
        return whitelistPendingAdditions.contains(account);
    }

    function isWhitelistRemovalPending(address account) external view returns (bool) {
        return whitelistPendingRemovals.contains(account);
    }

    function addSigner(address _signer) external onlyOwner {
        require(_signer != address(0), "Invalid signer address");
        require(!signers[_signer], "Signer already exists");

        signers[_signer] = true;
        emit SignerAdded(_signer);
    }

    function removeSigner(address _signer) external onlyOwner {
        require(signers[_signer], "Signer does not exist");

        signers[_signer] = false;
        emit SignerRemoved(_signer);
    }

    function pause() external onlyOwner {
        _pause();
    }

    function unpause() external onlyOwner {
        _unpause();
    }

}
