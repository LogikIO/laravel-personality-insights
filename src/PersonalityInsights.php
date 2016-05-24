<?php

namespace FindBrok\PersonalityInsights;

use FindBrok\PersonalityInsights\Contracts\InsightsContract;
use FindBrok\PersonalityInsights\Support\Util\ResultsProcessor;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Class PersonalityInsights
 *
 * @package FindBrok\PersonalityInsights
 */
class PersonalityInsights extends AbstractPersonalityInsights implements InsightsContract
{
    /**
     * Traits
     */
    use ResultsProcessor;

    /**
     * Full profile
     *
     * @var \Illuminate\Support\Collection
     */
    protected $profile;

    /**
     * The Cache repository
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new PersonalityInsights
     *
     * @param array $contentItems
     * @param Cache $cache
     */
    public function __construct($contentItems = [], Cache $cache)
    {
        //New Up a container
        $this->newUpContainer($contentItems);
        //Inject cache service in
        $this->cache = $cache;
    }

    /**
     * Get Full Insights From Watson API
     *
     * @return \Illuminate\Support\Collection
     */
    public function getProfileFromWatson()
    {
        //We have the request in cache and cache is on
        if ($this->cacheIsOn() && $this->cache->has($this->getContainer()->getCacheKey())) {
            //Return results from cache
            return $this->cache->get($this->getContainer()->getCacheKey());
        }

        //Cross the bridge
        $response = $this->makeBridge()->post('v2/profile', $this->getContainer()->getContentsForRequest());
        //Decode profile
        $profile = collect(json_decode($response->getBody()->getContents(), true));

        //Cache results if cache is on
        if ($this->cacheIsOn()) {
            $this->cache->put($this->getContainer()->getCacheKey(), $profile, $this->cacheLifetime());
        }

        //Return profile
        return $profile;
    }

    /**
     * Get Full Insights
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFullProfile()
    {
        //Profile not already loaded
        if (! $this->hasProfilePreLoaded()) {
            //Fetch Profile From Watson API
            $this->profile = $this->getProfileFromWatson();
        }
        //Return Results
        return $this->profile;
    }

    /**
     * Get an Insight Data
     *
     * @param string $id
     * @return \FindBrok\PersonalityInsights\Support\DataCollector\InsightNode|null
     */
    public function getInsight($id = '')
    {
        //No insight with this ID
        if (! $this->has($id, $this->collectTree())) {
            //We return null
            return null;
        }
        //Return Node
        return $this->getNodeById($id, $this->collectTree());
    }
}
