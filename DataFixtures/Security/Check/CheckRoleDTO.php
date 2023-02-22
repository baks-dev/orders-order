<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

namespace BaksDev\Orders\Order\DataFixtures\Security\Check;

use BaksDev\Users\Groups\Group\Entity\CheckRole\CheckRoleInterface;
use BaksDev\Users\Groups\Group\Entity\Event\GroupEvent;
use BaksDev\Users\Groups\Role\Entity\Event\RoleEventInterface;
use BaksDev\Users\Groups\Role\Type\RolePrefix\RolePrefix;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

final class CheckRoleDTO implements CheckRoleInterface
{

    /** Связь на событие группы */
    #[Assert\NotBlank]
    private GroupEvent $event;
    
    /** Префикс роли */
    #[Assert\NotBlank]
    private RolePrefix $role;
    
    /** Правила роли */
    #[Assert\Valid]
    private ArrayCollection $voter;

    public function __construct(GroupEvent $event, RoleEventInterface $roleDTO) {
        $this->event = $event;
        $this->voter = new ArrayCollection();
    
        $this->role = $roleDTO->getRole();
        
        foreach($roleDTO->getVoter() as $voter)
        {
            $CheckVoterDTO = new Voter\CheckVoterDTO();
            $CheckVoterDTO->setVoter($voter->getVoter());
            $this->addVoter($CheckVoterDTO);
        }
    }
    
    /**
     * @return GroupEvent
     */
    public function getEvent() : GroupEvent
    {
        return $this->event;
    }
    
    /* role */
    
    /**
     * @return RolePrefix
     */
    public function getRole() : RolePrefix
    {
        return $this->role;
    }
    
    
    /* VOTER */
    
    /**
     * @return ArrayCollection
     */
    public function getVoter() : ArrayCollection
    {
        return $this->voter;
    }

    public function addVoter(Voter\CheckVoterDTO $voter) : void
    {
        
        if(!$this->voter->contains($voter))
        {
            $this->voter->add($voter);
        }
    }
    
    /** Метод для инициализации и маппинга сущности на DTO в коллекции  */
    public function getVoterClass() : Voter\CheckVoterDTO
    {
        return new Voter\CheckVoterDTO();
    }
}

