<?php

namespace Knp\Component\Pager\Event\Subscriber\Filtration\Doctrine\ORM\Query;

use Doctrine\ORM\Query\TreeWalkerAdapter;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\WhereClause;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\LikeExpression;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalFactor;
use Doctrine\ORM\Query\AST\InputParameter;

/**
 * FilterBy Query TreeWalker for Filtration functionality
 * in doctrine paginator
 */
class FilterByWalker extends TreeWalkerAdapter
{
    /**
     * Sort key alias hint name
     */
    const HINT_PAGINATOR_FILTER_ALIAS = 'knp_paginator.filter.alias';

    /**
     * Sort key field hint name
     */
    const HINT_PAGINATOR_FILTER_FIELD = 'knp_paginator.filter.field';

    /**
     * Sort direction hint name
     */
    const HINT_PAGINATOR_FILTER_VALUE = 'knp_paginator.filter.value';

    /**
     * Walks down a SelectStatement AST node, modifying it to
     * sort the query like requested by url
     *
     * @param  SelectStatement $AST
     * @return void
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        $query = $this->_getQuery();
        $field = $query->getHint(self::HINT_PAGINATOR_FILTER_FIELD);
        $value = $query->getHint(self::HINT_PAGINATOR_FILTER_VALUE);
        $alias = $query->getHint(self::HINT_PAGINATOR_FILTER_ALIAS);

        $components = $this->_getQueryComponents();
        if ($alias !== false) {
            if (!array_key_exists($alias, $components)) {
                throw new \UnexpectedValueException("There is no component aliased by [{$alias}] in the given Query");
            }
            $meta = $components[$alias];
            if (!$meta['metadata']->hasField($field)) {
                throw new \UnexpectedValueException("There is no such field [{$field}] in the given Query component, aliased by [$alias]");
            }
        } else {
            if (!array_key_exists($field, $components)) {
                throw new \UnexpectedValueException("There is no component field [{$field}] in the given Query");
            }
        }

        if ($alias !== false) {
            $pathExpr = new PathExpression(PathExpression::TYPE_STATE_FIELD, $alias, $field);
            $pathExpr->type = PathExpression::TYPE_STATE_FIELD;
        } else {
            $pathExpression = $field;
        }

        $param = new InputParameter($field);
        $param->name = $field;
        if (false !== strpos($value, '*')) {
            $value = str_replace('*', '%', $value);
            $comparisonExpr = new LikeExpression($pathExpr, $param);
        } else {
            $comparisonExpr = new ComparisonExpression($pathExpr, '=', $param);
        }
        $this->_getQuery()->setParameter($field, $value);

        $condPrimary = new ConditionalPrimary;
        $condPrimary->simpleConditionalExpression = $comparisonExpr;

        if ($AST->whereClause) {
            // There is already a WHERE clause, so append the conditions
            $whereClause = $AST->whereClause;
            $condExpr = $whereClause->conditionalExpression;

            // Since Phase 1 AST optimizations were included, we need to re-add the ConditionalExpression
            if ( ! ($condExpr instanceof ConditionalExpression)) {
                $condExpr = new ConditionalExpression(array($condExpr));

                $whereClause->conditionalExpression = $condExpr;
            }

            $factor = new ConditionalFactor($condPrimary);
            $existingTerms = $whereClause->conditionalExpression->conditionalTerms;

            if (count($existingTerms) > 1) {
                // More than one term, so we need to wrap all these terms in a single root term
                // i.e: "WHERE u.name = :foo or u.other = :bar" => "WHERE (u.name = :foo or u.other = :bar) AND <our condition>"

                $primary = new ConditionalPrimary;
                $primary->conditionalExpression = new ConditionalExpression($existingTerms);
                $existingFactor = new ConditionalFactor($primary);
                $term = new ConditionalTerm(array_merge(array($existingFactor), array($factor)));

                $AST->whereClause->conditionalExpression->conditionalTerms = array($term);
            } else {
                // Just one term so we can simply append our factors to that term
                $singleTerm = $AST->whereClause->conditionalExpression->conditionalTerms[0];

                // Since Phase 1 AST optimizations were included, we need to re-add the ConditionalExpression
                if ( ! ($singleTerm instanceof ConditionalTerm)) {
                    $singleTerm = new ConditionalTerm(array($singleTerm));

                    $AST->whereClause->conditionalExpression->conditionalTerms[0] = $singleTerm;
                }

                $singleTerm->conditionalFactors = array_merge($singleTerm->conditionalFactors, array($factor));
                $AST->whereClause->conditionalExpression->conditionalTerms = array($singleTerm);
            }
        } else {
            $AST->whereClause = new WhereClause($condPrimary);
        }
    }
}
