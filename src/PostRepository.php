<?php

namespace App;

class PostRepository
{
    public function __construct()
    {
        session_start();
        if (!array_key_exists('users', $_SESSION)) {
            $_SESSION['users'] = [];
        }
    }

    public function all()
    {
        return array_values($_SESSION['users']);
    }

    public function find(string $id)
    {
        return $_SESSION['users'][$id];
    }

    public function destroy(string $id)
    {
        unset($_SESSION['users'][$id]);
    }

    public function save(array $user)
    {
        if (empty($user['nickname']) || empty($user['email'])) {
            $json = json_encode($user);
            throw new \Exception("Wrong data: {$json}");
        }
        if (!isset($user['id'])) {
            $user['id'] = uniqid();
        }
        $_SESSION['users'][$user['id']] = $user;
    }
}
