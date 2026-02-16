<?php

namespace App\Services;

use App\Models\Template;

class TemplateRenderer
{
    public function render(string $templateName, array $data = []): ?array
    {
        $template = Template::where('name', $templateName)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return null;
        }

        return [
            'subject' => $template->renderSubject($data),
            'content' => $template->render($data),
            'template_id' => $template->id,
        ];
    }
}
