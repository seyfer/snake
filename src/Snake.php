<?php

namespace PHPSnake;

/**
 * Created by PhpStorm.
 * User: seyfer
 * Date: 11/1/16
 */
class Snake
{

    /** @var string */
    private $name;

    /** @var string */
    private $direction;

    /** @var int */
    private $size = 0;

    const DIRECTIONS = ['UP', 'DOWN', 'LEFT', 'RIGHT'];

    /**
     * Snake constructor.
     * @param string|null $name
     */
    public function __construct(string $name = null)
    {
        if ($name === null) {
            $this->name = $this->generateRandomName();
        } else {
            $this->name = $name;
        }
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @param string $direction
     * @return Snake
     */
    public function setDirection(string $direction) : Snake
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, Snake::DIRECTIONS)) {
            throw new \InvalidArgumentException(
                'Invalid direction. Up, down, left, and right supported!'
            );
        }
        $this->direction = $direction;
        echo $this->name . ' is going ' . $direction . "\n";

        return $this;
    }

    /**
     * @param int $length
     * @return string
     */
    private function generateRandomName(int $length = 6) : string
    {
        $length = ($length > 3) ? $length : 6;
        $name   = '';

        $consonants = 'bcdfghklmnpqrstvwxyz';
        $vowels     = 'aeiou';

        for ($i = 0; $i < $length; $i++) {
            if ($i % 2 == 0) {
                $name .= $consonants[rand(0, strlen($consonants) - 1)];
            } else {
                $name .= $vowels[rand(0, strlen($vowels) - 1)];
            }
        }

        return ucfirst($name);
    }
}