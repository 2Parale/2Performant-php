<?php

namespace TPerformant\API\Model;

class Commission {
    private $id;
    private $userId;
    private $actionId;
    private $amount;
    private $status;
    private $affrequestId;
    private $description;
    private $createdAt;
    private $updatedAt;
    private $reason;
    private $statsTags;
    private $history;
    private $currency;
    private $workingCurrencyCode;
    private $programId;
    private $amountInWorkingCurrency;
    private $program = null;
    private $publicActionData = null;
    private $publicClickData = null;

    public function getId()
	{
		return $this->id;
	}

    public function getUserId()
	{
		return $this->userId;
	}

    public function getActionId()
	{
		return $this->actionId;
	}

    public function getAmount()
	{
		return $this->amount;
	}

    public function getStatus()
	{
		return $this->status;
	}

    public function getAffrequestId()
	{
		return $this->affrequestId;
	}

    public function getDescription()
	{
		return $this->description;
	}

    public function getCreatedAt()
	{
		return $this->createdAt;
	}

    public function getUpdatedAt()
	{
		return $this->updatedAt;
	}

    public function getReason()
	{
		return $this->reason;
	}

    public function getStatsTags()
	{
		return $this->statsTags;
	}

    public function getHistory()
	{
		return $this->history;
	}

    public function getCurrency()
	{
		return $this->currency;
	}

    public function getWorkingCurrencyCode()
	{
		return $this->workingCurrencyCode;
	}

    public function getProgramId()
	{
		return $this->programId;
	}

    public function getAmountInWorkingCurrency()
	{
		return $this->amountInWorkingCurrency;
	}

    public function getProgram()
	{
		return $this->program;
	}

    public function getPublicActionData()
	{
		return $this->publicActionData;
	}

    public function getPublicClickData()
	{
		return $this->publicClickData;
	}

}
