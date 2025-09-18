<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, HttpClientInterface $client): Response
    {
        dump($client);
        $results = $request->query->get('stream') === '1' ? $this->streamPackageData($client) : $this->getPackageData($client);
        dump($results);
        return $this->render('home/index.html.twig', [
            'controller_name' => sprintf('Using <code>%s</code> to compare with CachingHttpClient', $client::class),
        ]);
    }

    #[Route('/test-store', name: 'app_test_cache_store')]
    public function testCacheStore(Request $request, HttpClientInterface $client): Response
    {
        $dir = $this->getParameter('kernel.cache_dir') . '/http_client/';
        $store = new Store($dir);
        //Remove cache already in place to see the effect of caching when re-running the test
        $filesystem = new Filesystem();
        $filesystem->remove($dir);
        $client = new CachingHttpClient($client, $store);
        dump($client);
        $results = $request->query->get('stream') === '1' ? $this->streamPackageData($client) : $this->getPackageData($client);
        dump($results);
        return $this->render('home/index.html.twig', [
            'controller_name' => sprintf('Using <code>%s</code> with <code>%s</code>', $client::class, $store::class),
        ]);
    }

    #[Route('/test-cache', name: 'app_test_cache_tag_aware')]
    public function testCacheTagAware(Request $request, HttpClientInterface $client): Response
    {
        $cache =  new TagAwareAdapter(new ArrayAdapter());
        //Remove cache already in place to see the effect of caching when re-running the test
        $cache->clear();
        $client = new CachingHttpClient($client, $cache);
        dump($client);
        $results = $request->query->get('stream') === '1' ? $this->streamPackageData($client) : $this->getPackageData($client);
        dump($results);
        return $this->render('home/index.html.twig', [
            'controller_name' => sprintf('Using <code>%s</code> with <code>%s</code>', $client::class, $cache::class),
        ]);
    }

    private function getPackageData(HttpClientInterface $client): array
    {
        $packages = ['console', 'http-kernel', 'routing', 'yaml'];
        $responses = [];
        foreach ($packages as $package) {
            $uri = sprintf('https://repo.packagist.org/p2/symfony/%s.json', $package);
            // send all requests concurrently (they won't block until response content is read)
            $responses[$package] = $client->request('GET', $uri);
        }

        $results = [];
        // iterate through the responses and read their content
        foreach ($responses as $package => $response) {
            // process response data somehow ...
            $results[$package] = $response->toArray();
        }
        return $results;
    }

    private function streamPackageData(HttpClientInterface $client): array
    {
        $packages = ['console', 'http-kernel', 'routing', 'yaml'];
        $responses = [];
        foreach ($packages as $package) {
            $uri = sprintf('https://repo.packagist.org/p2/symfony/%s.json', $package);
            // send all requests concurrently (they won't block until response content is read)
            $responses[$package] = $client->request('GET', $uri, ['user_data' => $package]);
        }

        $results = [];
        // iterate through the responses and read their content
        foreach ($client->stream($responses) as $response => $chunk) {
            if ($chunk->isFirst()) {
                // the $response headers just arrived
                // $response->getHeaders() is now non-blocking
            } elseif ($chunk->isLast()) {
                // the full $response body has been received
                // $response->getContent() is now non-blocking
                $results[$response->getInfo('user_data')] = $response->toArray();
            } else {
                // $chunk->getContent() returns a piece of the body that just arrived
            }
        }
        return $results;
    }
}
