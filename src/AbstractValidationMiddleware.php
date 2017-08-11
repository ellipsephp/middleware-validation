<?php declare(strict_types=1);

namespace Ellipse\Validation;

use Psr\Http\Message\ServerRequestInterface;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;

use Ellipse\Validation\ValidatorFactory;
use Ellipse\Validation\Exceptions\DataInvalidException;

abstract class AbstractValidationMiddleware implements MiddlewareInterface
{
    /**
     * The validator factory.
     *
     * @var \Ellipse\Validation\ValidatorFactory
     */
    private $factory;

    /**
     * Return a new AbstractValidationMiddleware.
     *
     * @param string $locale
     * @return \Ellipse\Validation\AbstractValidationMiddleware
     */
    public static function create(string $locale = 'en'): AbstractValidationMiddleware
    {
        $factory = ValidatorFactory::create($locale);

        return self::withFactory($factory);
    }

    /**
     * Return a new AbstractValidationMiddleware using the given validator
     * factory.
     *
     * @param \Ellipse\Validation\ValidatorFactory $factory
     * @return \Ellipse\Validation\AbstractValidationMiddleware
     */
    public static function withFactory(ValidatorFactory $factory): AbstractValidationMiddleware
    {
        return new static($factory);
    }

    /**
     * Set up a validator middleware with a validator factory.
     *
     * @param \Ellipse\Validation\ValidatorFactory
     */
    public function __construct(ValidatorFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Return an array of rules.
     *
     * @return array
     */
    abstract public function getRules(): array;

    /**
     * Return an array associating field key to labels. Can be overrided by the
     * user.
     *
     * @return array
     */
    public function getLabels(): array
    {
        return [];
    }

    /**
     * Return an array associating field key to templates. It can be overrided
     * by the user.
     *
     * @return array
     */
    public function getTemplates(): array
    {
        return [];
    }

    /**
     * Get the rules, the labels and the templates and use the validator with
     * those data to validate the request input.
     *
     * @param \Psr\Http\Message\ServerRequestInterface  $request
     * @param \Psr\Http\Message\DelegateInterface       $delegate
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Ellipse\Validation\Exceptions\DataInvalidException
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $post = $request->getParsedBody();
        $files = $request->getUploadedFiles();

        $input = array_merge($post, $files);

        $rules = $this->getRules();
        $labels = $this->getLabels();
        $templates = $this->getTemplates();

        $validator = $this->factory->getValidator($rules)
            ->withLabels($labels)
            ->withTemplates($templates);

        $result = $validator->validate($input);

        if ($result->passed()) {

            return $delegate->process($request);

        }

        throw new DataInvalidException($result->getMessages());
    }
}
