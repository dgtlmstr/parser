<?php
namespace App\DTO;

class UserdataDTO {
    private $customerId = 0;
    private $firstName = "";
    private $lastName = "";
    private $cardNumber = "";

    /**
     * UserdataDTO constructor.
     * @param int $customerId
     * @param string $firstName
     * @param string $lastName
     * @param string $cardNumber
     */
    public function __construct(int $customerId, string $firstName, string $lastName, string $cardNumber)
    {
        $this->customerId = $customerId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->cardNumber = $cardNumber;
    }

    /**
     * @return int
     */
    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    /**
     * @param int $customerId
     */
    public function setCustomerId(int $customerId): void
    {
        $this->customerId = $customerId;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getCardNumber(): string
    {
        return $this->cardNumber;
    }

    /**
     * @param string $cardNumber
     */
    public function setCardNumber(string $cardNumber): void
    {
        $this->cardNumber = $cardNumber;
    }

    public function toArray() {
        return [
            (int)$this->customerId,
            (string)$this->firstName,
            (string)$this->lastName,
            (string)$this->cardNumber
        ];
    }
}
