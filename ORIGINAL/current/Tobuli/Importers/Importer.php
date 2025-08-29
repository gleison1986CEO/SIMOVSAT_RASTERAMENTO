<?php

namespace Tobuli\Importers;

use Cache;
use Tobuli\Entities\User;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Importers\Readers\ReaderInterface;
use Validator;

abstract class Importer implements ImporterInterface
{
    const STOP_ON_FAIL = 'stop_on_fail';

    protected $reader;
    protected $stop_on_fail = true;

    private $importIndex = null;

    abstract protected function getDefaults();
    abstract public static function getValidationRules(): array;
    abstract protected function importItem($data, $additionals = []);

    public static function getFieldDescriptions(): array
    {
        return [];
    }

    public function __construct(ReaderInterface $reader)
    {
        $this->reader = $reader;
    }

    public static function getImportFields(): array
    {
        return static::getValidationRules();
    }

    public function import($file, $additionals = [])
    {
        $items = $this->reader->read($file);

        if (empty($items)) {
            throw new ValidationException(trans('front.unsupported_format'));
        }

        $this->stop_on_fail = array_pull($additionals, self::STOP_ON_FAIL, true);

        foreach ($items as $index => $item) {
            $this->importIndex = $index + 1;

            $this->importItem($item, $additionals);
        }

        $this->importIndex = null;
    }

    protected function mergeDefaults($data)
    {
        $defaults = $this->getDefaults();

        foreach ($defaults as $key => $value) {
            if (isset($data[$key]) && empty($data[$key])) {
                unset($data[$key]);
            }
        }

        return empty($data) ? $defaults : array_merge($defaults, $data);
    }

    protected function setUser($data, $additionals)
    {
        if (isset($data['user_id'])) {
            $id = $data['user_id'];

            $user = Cache::store('array')->rememberForever("importer.user.$id", function() use ($id) {
                return User::find($id);
            });

            $data['user_id'] = $user ? $user->id : null;
        }

        if (empty($data['user_id']) && isset($additionals['user_id'])) {
            $id = $additionals['user_id'];

            $user = Cache::store('array')->rememberForever("importer.user.$id", function() use ($id) {
                return User::find($id);
            });

            $data['user_id'] = $user ? $user->id : null;
        }

        if (empty($data['user_id']))
            $data['user_id'] = auth()->id();

        return $data;
    }

    protected function validate($data)
    {
        $validator = Validator::make($data, static::getValidationRules());

        if ( ! $validator->fails()) {
            return true;
        }

        if ($this->stop_on_fail === true) {
            $errors = $this->importIndex === null ? $validator->messages() : $this->specifyErrors($validator, $this->importIndex);

            throw new ValidationException($errors);
        }

        return false;
    }

    private function specifyErrors(\Illuminate\Validation\Validator $validator, $index): array
    {
        $errors = [];
        $input = $validator->getData();

        foreach ($validator->messages()->messages() as $key => $message) {
            if (isset($message[0])) {
                $value = substr($input[$key], 0, 50);

                $message[0] = "#{$index}: {$message[0]} \"{$value}\"";
            }

            $errors[$key] = $message;
        }

        return $errors;
    }
}