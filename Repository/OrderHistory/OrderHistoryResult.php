<?php
/*
 * Copyright 2026.  Baks.dev <admin@baks.dev>
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

declare(strict_types=1);

namespace BaksDev\Orders\Order\Repository\OrderHistory;

use BaksDev\Core\Type\Modify\ModifyAction;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Users\Profile\UserProfile\Type\Event\UserProfileEventUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;

final readonly class OrderHistoryResult
{
    public function __construct(
        private string $event_id,
        private string $status,
        private ?string $mod_date,
        private ?string $action,
        private ?string $order_profile_id,
        private ?string $user_profile_id,
        private ?string $profile_username,
        private ?string $profile_avatar_name,
        private ?string $profile_avatar_ext,
        private ?string $profile_avatar_cdn,
    ) {}

    public function getEventId(): OrderEventUid
    {
        return new OrderEventUid($this->event_id);
    }

    public function getStatus(): OrderStatus
    {
        return new OrderStatus($this->status);
    }

    public function getModDate(): ?DateTimeImmutable
    {
        return false === empty($this->mod_date) ? new DateTimeImmutable($this->mod_date) : null;
    }

    public function getAction(): ?ModifyAction
    {
        return false === empty($this->action) ? new ModifyAction($this->action) : null;
    }

    public function getOrderProfileId(): ?UserProfileEventUid
    {
        return false === empty($this->order_profile_id) ? new UserProfileEventUid($this->order_profile_id) : null;
    }

    public function getUserProfileId(): ?UserProfileUid
    {
        return false === empty($this->user_profile_id) ? new UserProfileUid($this->user_profile_id) : null;
    }

    public function getProfileUsername(): ?string
    {
        return $this->profile_username;
    }

    public function getProfileAvatarName(): ?string
    {
        return $this->profile_avatar_name;
    }

    public function getProfileAvatarExt(): ?string
    {
        return $this->profile_avatar_ext;
    }

    public function getProfileAvatarCdn(): bool
    {
        return true === $this->profile_avatar_cdn;
    }
}