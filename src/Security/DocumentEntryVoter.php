<?php

namespace App\Security;

use App\Entity\DocumentEntry;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class DocumentEntryVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['edit_entry', 'delete_entry'], true)
            && $subject instanceof DocumentEntry;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) return false;

        /** @var DocumentEntry $entry */
        $entry = $subject;

        // Admins can do anything
        if (in_array('ROLE_ADMIN', $token->getRoleNames(), true)) return true;

        // Users can only edit/delete their own entries
        return $entry->getCreatedBy()?->getId() === $user->getId();
    }
}
