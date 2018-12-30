<?php

namespace BookmarkManager\ApiBundle\Form;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Type;
use BookmarkManager\ApiBundle\Entity\User;

class UserRegistration
{

    /**
     * @Assert\Email
     * @Assert\NotBlank
     * @Type("string")
     * @var string
     */
    public $email;

    /**
     * @Assert\NotBlank
     * @Type("string")
     * @var string
     */
    public $password;

    /**
     * @Assert\NotBlank
     * @Type("string")
     * @var string
     */
    public $username;

    public function getUser()
    {
        $user = new User();
        $user->setEmail($this->email);
        $user->setUsername($this->username);
        $user->setPlainPassword($this->password);
        $user->setEnabled(true);

        return $user;
    }
}