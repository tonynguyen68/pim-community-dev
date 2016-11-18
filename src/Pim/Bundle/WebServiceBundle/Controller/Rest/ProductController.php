<?php

namespace Pim\Bundle\WebServiceBundle\Controller\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use Pim\Component\Catalog\Model\ProductInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Product API controller
 *
 * @author    Filips Alpe <filips@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @RouteResource("product")
 * @NamePrefix("oro_api_")
 */
class ProductController extends FOSRestController
{
    /**
     * Get a single product
     *
     * @param Request $request
     * @param string  $identifier
     *
     * @return Response
     */
    public function getAction(Request $request, $identifier)
    {
        $userContext = $this->get('pim_user.context.user');
        $availableChannels = array_keys($userContext->getChannelChoicesWithUserChannel());
        $availableLocales = $userContext->getUserLocaleCodes();

        $channels = $request->get('channels', $request->get('channel', null));
        if ($channels !== null) {
            $channels = explode(',', $channels);

            foreach ($channels as $channel) {
                if (!in_array($channel, $availableChannels)) {
                    return new Response(sprintf('Channel "%s" does not exist or is not available', $channel), 403);
                }
            }
        }

        $locales = $request->get('locales', $request->get('locale', null));
        if ($locales !== null) {
            $locales = explode(',', $locales);

            foreach ($locales as $locale) {
                if (!in_array($locale, $availableLocales)) {
                    return new Response(sprintf('Locale "%s" does not exist or is not available', $locale), 403);
                }
            }
        }

        return $this->handleGetRequest($identifier, $channels, $locales);
    }

    public function listAction(Request $request)
    {
        $userContext = $this->get('pim_user.context.user');
        $availableChannels = array_keys($userContext->getChannelChoicesWithUserChannel());
        $availableLocales = $userContext->getUserLocaleCodes();

        $channels = $request->get('channels', $request->get('channel', null));
        if ($channels !== null) {
            $channels = explode(',', $channels);

            foreach ($channels as $channel) {
                if (!in_array($channel, $availableChannels)) {
                    return new Response(sprintf('Channel "%s" does not exist or is not available', $channel), 403);
                }
            }
        }

        $locales = $request->get('locales', $request->get('locale', null));
        if ($locales !== null) {
            $locales = explode(',', $locales);

            foreach ($locales as $locale) {
                if (!in_array($locale, $availableLocales)) {
                    return new Response(sprintf('Locale "%s" does not exist or is not available', $locale), 403);
                }
            }
        }

        $products = $this->container->get('pim_catalog.repository.product')->findBy([], [], 100);

        $data = [];
        foreach ($products as $product) {
            $a = $this->serializeProduct($product, $channels, $locales);
            $data[] = $a;
        }

        return new JsonResponse($data);
    }

    public function postAction(Request $request)
    {
        $products = json_decode($request->request->get('products'), true);

        foreach ($products as $standardProduct) {
            $product = $this->container->get('pim_catalog.repository.product')->findOneByIdentifier($standardProduct['identifier']);

            if (null === $product) {
                $product = $this->container->get('pim_catalog.builder.product')->createProduct($standardProduct['identifier']);
            }

            $this->container->get('pim_catalog.updater.product')->update($product, $standardProduct);

            $this->container->get('pim_catalog.saver.product')->save($product);

            echo sprintf("Product %s saved\n", $standardProduct['identifier']);
        }

        return new JsonResponse();
    }

    public function postSingleAction(Request $request)
    {
        $standardProduct = json_decode($request->request->get('product'), true);

        $product = $this->container->get('pim_catalog.repository.product')->findOneByIdentifier($standardProduct['identifier']);

        if (null === $product) {
            $product = $this->container->get('pim_catalog.builder.product')->createProduct($standardProduct['identifier']);
        }

        $this->container->get('pim_catalog.updater.product')->update($product, $standardProduct);

        $this->container->get('pim_catalog.saver.product')->save($product);

        echo sprintf("Product %s saved\n", $standardProduct['identifier']);

        return new JsonResponse();
    }

    /**
     * Return a single product
     *
     * @param string   $identifier
     * @param string[] $channels
     * @param string[] $locales
     *
     * @return Response
     */
    protected function handleGetRequest($identifier, $channels, $locales)
    {
        $productRepository = $this->container->get('pim_catalog.repository.product');
        $product = $productRepository->findOneByIdentifier($identifier);

        if (!$product) {
            return new Response(sprintf('Product "%s" not found', $identifier), 404);
        }

        try {
            $serializedData = $this->serializeProduct($product, $channels, $locales);
        } catch (AccessDeniedException $exception) {
            return new Response(sprintf('Access denied to the product "%s"', $product->getIdentifier()), 403);
        }

        return new JsonResponse($serializedData);
    }

    /**
     * Serialize a single product
     *
     * @param ProductInterface $product
     * @param string[]         $channels
     * @param string[]         $locales
     *
     * @return array
     */
    protected function serializeProduct(ProductInterface $product, $channels, $locales)
    {
        $url = $this->generateUrl(
            'oro_api_get_product',
            [
                'identifier' => $product->getIdentifier()->getData()
            ],
            true
        );
        $handler = $this->container->get('pim_webservice.handler.rest.product');
        $data = $handler->get($product, $channels, $locales, $url);

        return $data;
    }
}
