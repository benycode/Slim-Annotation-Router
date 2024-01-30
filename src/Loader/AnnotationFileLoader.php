<?php

declare(strict_types=1);

namespace Slim\AnnotationRouter\Loader;

/**
 * Class AnnotationFileLoader - Based on Symfony Annotation Loader
 *
 * @since 22.04.2019, Updated 2024-01-30
 * @author Daniel Tęcza, Benediktas Rukas
 * @package Slim\AnnotationRouter\Loader
 */
class AnnotationFileLoader
{
    /** @var \Slim\AnnotationRouter\Loader\AnnotationClassLoader */
    protected $loader;

    /**
     * @param \Slim\AnnotationRouter\Loader\AnnotationClassLoader|null $loader
     */
    public function __construct(AnnotationClassLoader $loader)
    {
        if (!\function_exists('token_get_all')) {
            throw new \LogicException('The Tokenizer extension is required for the routing annotation loaders.');
        }

        $this->loader = $loader;
    }

    /**
     * Loads from annotations from a file.
     *
     * @param string $filePath
     * @param string|null $type The resource type
     *
     * @return array A RouteCollection instance
     *
     * @throws \ReflectionException
     */
    public function load(string $filePath, string $type = null) : array
    {
        $collection = [];

        if ($class = $this->findClass($filePath)) {
            $reflection = new \ReflectionClass($class);

            if ($reflection->isAbstract()) {
                return $collection;
            }

            $collection[] = $this->loader->load($class);
        }

        gc_mem_caches();

        return $collection;
    }

    /**
     * @param string $resource
     * @param string|null $type
     *
     * @return bool
     */
    public function supports($resource, string $type = null) : bool
    {
        return \is_string($resource) && 'php' === pathinfo($resource, PATHINFO_EXTENSION) && (!$type || 'annotation' === $type);
    }

    /**
     * Returns the full class name for the first class in the file.
     *
     * @param string $file A PHP file path
     *
     * @return string|false Full class name if found, false otherwise
     */
    protected function findClass(string $filePath) : ?string
    {
        $tokens = \token_get_all(\file_get_contents($filePath));

        $namespace = '';
        $className = '';

        foreach ($tokens as $key => $token) {
            if (is_array($token) && T_NAMESPACE === $token[0]) {
                $namespace = '';
                while (isset($tokens[++$key][1])) {
                    $namespace .= $tokens[$key][1];
                }
            } elseif (is_array($token) && T_CLASS === $token[0]) {
                while (isset($tokens[++$key][1])) {
                    if(T_STRING === $tokens[$key][0]) {
                        $className .= $tokens[$key][1];
                        break;
                    }
                }
                break;
            }
        }

        $namespace = trim($namespace, " \t\n\r\0\x0B\\");
        $className = trim($className, " \t\n\r\0\x0B{");

        return $namespace ? $namespace . '\\' . $className : $className;
    }
}
