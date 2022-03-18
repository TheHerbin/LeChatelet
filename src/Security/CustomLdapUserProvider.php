<?php

namespace App\Security;

use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Security\LdapUser;
use Symfony\Component\Ldap\Security\LdapUserProvider;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\User\UserInterface;

class CustomLdapUserProvider extends LdapUserProvider
{
    private $defaultRoles;
    private $passwordAttribute;
    private $extraFields = array();

    /**
     * Loads a user from an LDAP entry.
     *
     * @param string $username
     * @param Entry $entry
     * @return UserInterface
     */
    protected function loadUser(string $username, Entry $entry)
    {
        $password = null;
        $extraFields = [];

        if (null !== $this->passwordAttribute) {
            $password = $this->getAttributeValue($entry, $this->passwordAttribute);
        }

        foreach ($this->extraFields as $field) {
            $extraFields[$field] = $this->getAttributeValue($entry, $field);
        }

        $results=array();
        foreach ($entry->getAttribute("memberOf") as $LdapGroupDn)
        {
            $results[]= "ROLE_".ldap_explode_dn($LdapGroupDn,1)[0];
        }

        if (!empty($results))
            $roles=$results;
        else
            $roles=$this->defaultRoles;

        return new LdapUser($entry, $username, $password, $roles, $extraFields);
    }

    private function getAttributeValue(Entry $entry, string $attribute)
    {
        if (!$entry->hasAttribute($attribute)) {
            throw new InvalidArgumentException(sprintf('Missing attribute "%s" for user "%s".', $attribute, $entry->getDn()));
        }

        $values = $entry->getAttribute($attribute);

        if (1 !== \count($values)) {
            throw new InvalidArgumentException(sprintf('Attribute "%s" has multiple values.', $attribute));
        }

        return $values[0];
    }

}