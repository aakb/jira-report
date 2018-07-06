<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CustomerRepository")
 */
class Customer
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $Title;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $Internal;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    public $PricePerHour;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $EAN;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $Debitor;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $CVR;

    public function getId()
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->Title;
    }

    public function setTitle(string $Title): self
    {
        $this->Title = $Title;

        return $this;
    }

    public function getInternal(): ?bool
    {
        return $this->Internal;
    }

    public function setInternal(?bool $Internal): self
    {
        $this->Internal = $Internal;

        return $this;
    }

    public function getEAN(): ?string
    {
        return $this->EAN;
    }

    public function setEAN(?string $EAN): self
    {
        $this->EAN = $EAN;

        return $this;
    }

    public function getPricePerHour(): ?float
    {
        return $this->PricePerHour;
    }

    public function setPricePerHour(?float $PricePerHour): self
    {
        $this->PricePerHour = $PricePerHour;

        return $this;
    }

    public function getDebitor(): ?string
    {
        return $this->Debitor;
    }

    public function setDebitor(?string $Debitor): self
    {
        $this->Debitor = $Debitor;

        return $this;
    }

    public function getCVR(): ?string
    {
        return $this->CVR;
    }

    public function setCVR(?string $CVR): self
    {
        $this->CVR = $CVR;

        return $this;
    }
}
