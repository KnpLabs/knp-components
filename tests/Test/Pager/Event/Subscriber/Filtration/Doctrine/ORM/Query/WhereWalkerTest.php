<?php

namespace Knp\Component\Pager\Event\Subscriber\Filtration\Doctrine\ORM\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\ConditionalFactor;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\FromClause;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectClause;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\WhereClause;
use Doctrine\ORM\Query\ParserResult;
use PHPUnit\Framework\TestCase;

class WhereWalkerTest extends TestCase
{
    private Query $query;
    private ParserResult $parserResult;
    private array $queryComponents;

    protected function setUp(): void
    {
        // Mock Doctrine Query and ParserResult
        $this->query = $this->createMock(Query::class);
        $this->parserResult = $this->createMock(ParserResult::class);

        // Set up query hints
        $this->query->method('getHint')
            ->willReturnMap([
                [WhereWalker::HINT_PAGINATOR_FILTER_VALUE, 'test'],
                [WhereWalker::HINT_PAGINATOR_FILTER_COLUMNS, ['tdf.name']],
                [WhereWalker::HINT_PAGINATOR_FILTER_CASE_INSENSITIVE, false],
            ]);

        // Mock query components
        $this->queryComponents = [
            'tdf' => [
                'metadata' => $this->createMock(\Doctrine\ORM\Mapping\ClassMetadata::class),
            ],
        ];
        $this->queryComponents['tdf']['metadata']->method('hasField')->willReturn(true);
        $this->queryComponents['tdf']['metadata']->method('getTypeOfField')->willReturn('string');
    }

    public function testWalkSelectStatementWithComplexConditionalTerm(): void
    {
        // Create a complex WHERE clause AST
        $conditionalPrimary = new ConditionalPrimary();
        $conditionalPrimary->simpleConditionalExpression = new ComparisonExpression(
            new PathExpression(PathExpression::TYPE_STATE_FIELD, 'tdf.objects', 'tdf.objects'),
            'MEMBER OF',
            new InputParameter(':object')
        );

        $conditionalFactor = new ConditionalFactor($conditionalPrimary);
        $conditionalTerm = new ConditionalTerm([$conditionalFactor]);

        $conditionalPrimary2 = new ConditionalPrimary();
        $conditionalPrimary2->simpleConditionalExpression = new ComparisonExpression(
            new PathExpression(PathExpression::TYPE_STATE_FIELD, 'oacc_obj_res.read', 'oacc_obj_res.read'),
            '=',
            new Literal(Literal::NUMERIC, '1')
        );

        $conditionalFactor2 = new ConditionalFactor($conditionalPrimary2);
        $conditionalTerm2 = new ConditionalTerm([$conditionalFactor2]);

        $conditionalExpression = new ConditionalExpression([$conditionalTerm, $conditionalTerm2]);

        // Create SelectStatement with WhereClause
        $selectClause = new SelectClause([], false);
        $fromClause = new FromClause([
            new IdentificationVariableDeclaration(
                new RangeVariableDeclaration('Entity', 'tdf'),
                null,
                []
            ),
        ]);
        $whereClause = new WhereClause($conditionalExpression);
        $selectStatement = new SelectStatement($selectClause, $fromClause);
        $selectStatement->whereClause = $whereClause;

        // Create WhereWalker instance
        $whereWalker = new WhereWalker($this->query, $this->parserResult, $this->queryComponents);

        // Invoke walkSelectStatement
        $whereWalker->walkSelectStatement($selectStatement);

        // Assert that the whereClause is still present and contains a ConditionalExpression
        $this->assertInstanceOf(WhereClause::class, $selectStatement->whereClause);
        $this->assertInstanceOf(ConditionalExpression::class, $selectStatement->whereClause->conditionalExpression);
        $this->assertGreaterThanOrEqual(2, count($selectStatement->whereClause->conditionalExpression->conditionalTerms));
    }

    public function testWalkSelectStatementWithConditionalFactor(): void
    {
        // Create a simple WHERE clause with a filter parameter
        $conditionalPrimary = new ConditionalPrimary();
        $conditionalPrimary->simpleConditionalExpression = new ComparisonExpression(
            new PathExpression(PathExpression::TYPE_STATE_FIELD, 'tdf.name', 'tdf.name'),
            '=',
            new InputParameter(':knp_filter')
        );

        $conditionalFactor = new ConditionalFactor($conditionalPrimary);
        $conditionalTerm = new ConditionalTerm([$conditionalFactor]);
        $conditionalExpression = new ConditionalExpression([$conditionalTerm]);

        // Create SelectStatement with WhereClause
        $selectClause = new SelectClause([], false);
        $fromClause = new FromClause([
            new IdentificationVariableDeclaration(
                new RangeVariableDeclaration('Entity', 'tdf'),
                null,
                []
            ),
        ]);
        $whereClause = new WhereClause($conditionalExpression);
        $selectStatement = new SelectStatement($selectClause, $fromClause);
        $selectStatement->whereClause = $whereClause;

        // Create WhereWalker instance
        $whereWalker = new WhereWalker($this->query, $this->parserResult, $this->queryComponents);

        // Invoke walkSelectStatement
        $whereWalker->walkSelectStatement($selectStatement);

        // Assert that the whereClause is still present and contains a ConditionalExpression with an additional factor
        $this->assertInstanceOf(WhereClause::class, $selectStatement->whereClause);
        $this->assertInstanceOf(ConditionalExpression::class, $selectStatement->whereClause->conditionalExpression);
        $this->assertGreaterThanOrEqual(2, count($selectStatement->whereClause->conditionalExpression->conditionalTerms[0]->conditionalFactors));
    }
}
