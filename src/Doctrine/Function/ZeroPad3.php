<?php

namespace App\Doctrine\Function;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/** DQL function ZEROPAD3(x) — formats an integer as a zero-padded 3-digit string (SQLite only) */
class ZeroPad3 extends FunctionNode
{
    private mixed $value;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->value = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $walker): string
    {
        return "printf('%03d', " . $this->value->dispatch($walker) . ')';
    }
}
