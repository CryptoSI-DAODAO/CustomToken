// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

import "@openzeppelin/contracts/token/ERC20/ERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/token/ERC20/extensions/ERC20Permit.sol";

contract CryptosiTest is ERC20, ERC20Permit, Ownable {
    address public constant BURN_ADDRESS = 0x000000000000000000000000000000000000dEaD;
    address public constant DAO_TREASURY = 0x22EdfeFf52B46B5D3b4244996D9C53429cd67e14;
    address public constant FIRST_WHITELISTED_ADDRESS = 0x5024FE2320Fa6aC6a9209199A0dFDa6b94bd2FdF;

    uint256 public constant INITIAL_SUPPLY = 100000000000 * (10 ** 18);
    uint256 public constant TRANSFER_FEE_RATE = 50; // 5%

    mapping(address => bool) public whitelisted;

    event Whitelisted(address indexed account, bool status);

    constructor() ERC20("CryptosiTest", "CRDT") ERC20Permit("CryptosiTest") Ownable(msg.sender) {
        _mint(msg.sender, INITIAL_SUPPLY);
        whitelisted[FIRST_WHITELISTED_ADDRESS] = true;
        emit Whitelisted(FIRST_WHITELISTED_ADDRESS, true);
    }

    function transfer(address recipient, uint256 amount) public virtual override returns (bool) {
        uint256 fee = (amount * TRANSFER_FEE_RATE) / 1000; // Calculate transfer fee
        uint256 transferAmount = amount - fee;

        super.transfer(BURN_ADDRESS, fee / 2); // Send 50% of fee to burn address
        super.transfer(DAO_TREASURY, fee / 2); // Send 50% of fee to DAO treasury address
        super.transfer(recipient, transferAmount); // Transfer remaining amount to recipient

        return true;
    }

    function addToWhitelist(address account) external onlyOwner {
        whitelisted[account] = true;
        emit Whitelisted(account, true);
    }

    function removeFromWhitelist(address account) external onlyOwner {
        whitelisted[account] = false;
        emit Whitelisted(account, false);
    }
}
