<?php
/**
 * Modal Components
 * Provides reusable modal components and utilities
 */

/**
 * Generate modal HTML structure
 * 
 * @param string $id Modal ID
 * @param string $title Modal title
 * @param string $content Modal content
 * @param array $options Additional options
 * @return string HTML for modal
 */
function generateModal(string $id, string $title, string $content, array $options = []): string {
    $size = $options['size'] ?? 'max-w-2xl';
    $closeable = $options['closeable'] ?? true;
    
    $html = "<div id=\"{$id}\" class=\"hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50\">";
    $html .= "<div class=\"bg-card p-6 rounded-lg w-full {$size} max-h-[90vh] overflow-y-auto\">";
    
    if ($closeable) {
        $html .= "<div class=\"flex justify-between items-center mb-4\">";
        $html .= "<h2 class=\"text-xl font-bold\">{$title}</h2>";
        $html .= "<button type=\"button\" onclick=\"hideModal('{$id}')\" class=\"text-muted-foreground hover:text-foreground\">";
        $html .= "<i data-lucide=\"x\" class=\"h-6 w-6\"></i>";
        $html .= "</button>";
        $html .= "</div>";
    } else {
        $html .= "<h2 class=\"text-xl font-bold mb-4\">{$title}</h2>";
    }
    
    $html .= $content;
    $html .= "</div>";
    $html .= "</div>";
    
    return $html;
}

/**
 * Generate form field HTML
 * 
 * @param string $name Field name
 * @param string $label Field label
 * @param string $type Field type
 * @param array $options Field options
 * @return string HTML for form field
 */
function generateFormField(string $name, string $label, string $type = 'text', array $options = []): string {
    $required = $options['required'] ?? false;
    $placeholder = $options['placeholder'] ?? '';
    $value = $options['value'] ?? '';
    $class = $options['class'] ?? 'w-full px-3 py-2 border rounded-md';
    
    $html = "<div class=\"mb-4\">";
    $html .= "<label class=\"block text-sm font-medium mb-1\">{$label}" . ($required ? ' *' : '') . "</label>";
    
    switch ($type) {
        case 'textarea':
            $rows = $options['rows'] ?? 3;
            $html .= "<textarea name=\"{$name}\" rows=\"{$rows}\" class=\"{$class}\" placeholder=\"{$placeholder}\">{$value}</textarea>";
            break;
        case 'select':
            $options_html = $options['options'] ?? [];
            $html .= "<select name=\"{$name}\" class=\"{$class}\">";
            foreach ($options_html as $val => $text) {
                $selected = ($val == $value) ? ' selected' : '';
                $html .= "<option value=\"{$val}\"{$selected}>{$text}</option>";
            }
            $html .= "</select>";
            break;
        default:
            $html .= "<input type=\"{$type}\" name=\"{$name}\" class=\"{$class}\" placeholder=\"{$placeholder}\" value=\"{$value}\"" . ($required ? ' required' : '') . ">";
            break;
    }
    
    $html .= "</div>";
    return $html;
}

/**
 * Generate button HTML
 * 
 * @param string $text Button text
 * @param string $type Button type
 * @param array $options Button options
 * @return string HTML for button
 */
function generateButton(string $text, string $type = 'button', array $options = []): string {
    $class = $options['class'] ?? 'px-4 py-2 rounded-md';
    $onclick = $options['onclick'] ?? '';
    
    $html = "<button type=\"{$type}\" class=\"{$class}\"";
    if ($onclick) {
        $html .= " onclick=\"{$onclick}\"";
    }
    $html .= ">{$text}</button>";
    
    return $html;
}
