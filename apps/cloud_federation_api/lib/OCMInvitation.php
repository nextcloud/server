<?php

declare(strict_types=1);
namespace OCA\CloudFederationApi;

class OCMInvitation {
    public bool $accepted;
    public string $recipient_email;
    public string $recipient_name;
    public string $recipient_provider;
    public string $recipient_user_id;
    public string $token;
    public string $user_id;
    public \Datetime $acceptedAt;
    public \Datetime $createdAt;
    public \Datetime $expiresAt;

}
