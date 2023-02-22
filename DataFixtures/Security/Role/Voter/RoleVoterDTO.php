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

namespace BaksDev\Orders\Order\DataFixtures\Security\Role\Voter;

use BaksDev\Users\Groups\Role\Entity\Voters\RoleVoterInterface;
use BaksDev\Users\Groups\Role\Type\VoterPrefix\VoterPrefix;
use BaksDev\Core\Type\Locale\Locale;
use Doctrine\Common\Collections\ArrayCollection;

final class RoleVoterDTO implements RoleVoterInterface
{
    
    public const VOTERS = [
      
      'NEW' => [
        'ru' => 'Добавить',
        'en' => 'New'
      ],
      
      'EDIT' => [
        'ru' => 'Редактировать',
        'en' => 'Edit'
      ],
      
      'DELETE' => [
        'ru' => 'Удалить',
        'en' => 'Delete'
      ],
      
      'RECYCLEBIN' => [
        'ru' => 'Корзина',
        'en' => 'Recyclebin'
      ],
      
      'SETTINGS' => [
        'ru' => 'Настройки',
        'en' => 'Settings'
      ],
      
      'REMOVE' => [
        'ru' => 'Очистить',
        'en' => 'Remove'
      ],
      
      'RESTORE' => [
        'ru' => 'Восстановить',
        'en' => 'Restore'
      ],
      
      'HISTORY' => [
        'ru' => 'История',
        'en' => 'History'
      ],
      
      'ROLLBACK' => [
        'ru' => 'Откатить',
        'en' => 'Rollback'
      ],
    
    ];
    
    /** Вспомогательное свойство  */
    private string $key;
    
    /** Префикс правила */
    private VoterPrefix $voter;
    
    /** Настройки локали */
    private ArrayCollection $translate;
    
  
    public function __construct() { $this->translate = new ArrayCollection(); }
    
    
    /* VOTER */


    /**
     * @return VoterPrefix
     */
    public function getVoter() : VoterPrefix
    {
        return $this->voter;
    }
    
    /**
     * @param VoterPrefix $voter
     */
    public function setVoter(VoterPrefix $voter) : void
    {
        $this->voter = $voter;
    }
    
    /* TRANSLATE */
    
    /**
     * @return ArrayCollection
     */
    public function getTranslate() : ArrayCollection
    {
        /* Вычисляем расхождение и добавляем неопределенные локали */
        foreach(Locale::diffLocale($this->translate) as $locale)
        {
            $TransFormDTO = new Trans\VoterTransDTO();
            $TransFormDTO->setLocal($locale);
            $TransFormDTO->setName(self::VOTERS[$this->key][$locale->getValue()]);

            $this->addTranslate($TransFormDTO);
        }
        
        return $this->translate;
    }

    public function addTranslate(Trans\VoterTransDTO $translate) : void
    {
        $this->translate->add($translate);
    }
    
    /** Метод для инициализации и маппинга сущности на DTO в коллекции  */
    public function getTranslateClass() : Trans\VoterTransDTO
    {
        return new Trans\VoterTransDTO();
    }
    
    /* KEY */
    
    
    /**
     * @param string $key
     */
    public function setKey(string $key) : void
    {
        $this->key = $key;
    }
    

    

}

