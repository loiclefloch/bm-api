<?php

namespace BookmarkManager\ApiBundle\Annotation;

/**
 * Entity annotation driver
 *
 * @Annotation
 */
class ApiErrors
{

    // -- The index in the table
    const CODE_INDEX = 0;
    const MESSAGE_INDEX = 1;

    /**
     * @var array
     *
     * code: The api error code
     * message: The api error message for the developer
     */
    private $errors = array();

    public function __construct(array $data)
    {
        if (!isset($data['value'])) {
            throw new \InvalidArgumentException('errors array is not defined');
        } else {
            foreach ($data['value'] as $error) {
                if (count($error) != 2) {
                    throw new \InvalidArgumentException('An error must have a code and a message ');
                }
                $this->addError($error);
            }
        }
    }

    /**
     * Return the errors
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Push a new error to the error's list
     *
     * @param $error
     */
    public function addError($error)
    {
        array_push($this->errors, $error);
    }

    /**
     * Return the errors formatted as a printable html table
     * @return string
     */
    public function getErrorsFormattedAsHtmlTable()
    {
        $result = '';
        foreach ($this->errors as $error) {
            $result = $result . '<tr><td>' . $error[ApiErrors::CODE_INDEX] . "</td><td>" . $error[ApiErrors::MESSAGE_INDEX] . "</td></tr>";
        }

        return $result;
    }
}