<?php

/*
 * This file is part of the Solarium package.
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code.
 */

namespace Solarium\QueryType\MoreLikeThis;

use Solarium\Core\Client\Request;
use Solarium\Core\Query\QueryInterface;
use Solarium\QueryType\Select\RequestBuilder as SelectRequestBuilder;

/**
 * Build a MoreLikeThis request.
 */
class RequestBuilder extends SelectRequestBuilder
{
    /**
     * Build request for a MoreLikeThis query.
     *
     * @param QueryInterface|Query $query
     *
     * @return Request
     */
    public function build(QueryInterface|Query $query): Request
    {
        $request = parent::build($query);

        // add MLT params to request
        $request->addParam('mlt.fl', implode(',', $query->getMltFields()));
        $request->addParam('mlt.mintf', $query->getMinimumTermFrequency());
        $request->addParam('mlt.mindf', $query->getMinimumDocumentFrequency());
        $request->addParam('mlt.maxdf', $query->getMaximumDocumentFrequency());
        $request->addParam('mlt.maxdfpct', $query->getMaximumDocumentFrequencyPercentage());
        $request->addParam('mlt.minwl', $query->getMinimumWordLength());
        $request->addParam('mlt.maxwl', $query->getMaximumWordLength());
        $request->addParam('mlt.maxqt', $query->getMaximumQueryTerms());
        $request->addParam('mlt.maxntp', $query->getMaximumNumberOfTokens());
        $request->addParam('mlt.boost', $query->getBoost());
        $request->addParam('mlt.qf', $query->getQueryFields());
        $request->addParam('mlt.match.include', $query->getMatchInclude());
        $request->addParam('mlt.match.offset', $query->getMatchOffset());
        $request->addParam('mlt.interestingTerms', $query->getInterestingTerms());

        // convert query to stream if necessary
        if (true === $query->getQueryStream()) {
            $request->removeParam('q');
            $request->setRawData($query->getQuery());
            $request->setMethod(Request::METHOD_POST);
            $request->setContentType(Request::CONTENT_TYPE_TEXT_PLAIN);
        }

        return $request;
    }
}
