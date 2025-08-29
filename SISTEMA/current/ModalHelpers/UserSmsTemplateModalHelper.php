<?php namespace ModalHelpers;

use CustomFacades\Repositories\UserSmsTemplateRepo;
use CustomFacades\Validators\UserSmsTemplateFormValidator;
use Tobuli\Exceptions\ValidationException;

class UserSmsTemplateModalHelper extends ModalHelper
{
    public function get()
    {
        $this->checkException('user_sms_templates', 'view');

        $this->data['filter']['user_id'] = $this->user->id;
        $user_sms_templates = UserSmsTemplateRepo::searchAndPaginate($this->data, 'id', 'desc', 10);

        if ($this->api) {
            $user_sms_templates = $user_sms_templates->toArray();
            $user_sms_templates['url'] = route('api.get_user_sms_templates');
        }

        return compact('user_sms_templates');
    }

    public function create()
    {
        $this->checkException('user_sms_templates', 'store');

        UserSmsTemplateFormValidator::validate('create', $this->data);

        $item = UserSmsTemplateRepo::create([
            'user_id' => $this->user->id,
            'title' => $this->data['title'],
            'message' => $this->data['message']
        ]);

        return ['status' => 1, 'item' => $item];
    }

    public function editData()
    {
        $id = array_key_exists('user_sms_template_id', $this->data) ? $this->data['user_sms_template_id'] : request()->route('user_sms_templates');
        
        $item = UserSmsTemplateRepo::find($id);

        $this->checkException('user_sms_templates', 'edit', $item);

        return compact('item');
    }

    public function edit()
    {
        $item = UserSmsTemplateRepo::find($this->data['id']);

        $this->checkException('user_sms_templates', 'update', $item);

        UserSmsTemplateFormValidator::validate('update', $this->data);

        UserSmsTemplateRepo::update($item->id, [
            'title' => $this->data['title'],
            'message' => $this->data['message']
        ]);

        return ['status' => 1];
    }

    public function getMessage()
    {
        $id = array_key_exists('user_sms_template_id', $this->data) ? $this->data['user_sms_template_id'] : $this->data['id'];
        
        $item = UserSmsTemplateRepo::find($id);

        $this->checkException('user_sms_templates', 'show', $item);

        return ['status' => 1, 'message' => $item->message];
    }

    public function doDestroy($id)
    {
        $item = UserSmsTemplateRepo::find($id);

        $this->checkException('user_sms_templates', 'remove', $item);

        return compact('item');
    }

    public function destroy()
    {
        $id = array_key_exists('user_sms_template_id', $this->data) ? $this->data['user_sms_template_id'] : $this->data['id'];
        
        $item = UserSmsTemplateRepo::find($id);

        $this->checkException('user_sms_templates', 'remove', $item);

        UserSmsTemplateRepo::delete($id);
        
        return ['status' => 1];
    }
}