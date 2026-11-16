<?php

namespace Core;

/**
 * Base controller – shortcuts every controller uses: render a view, redirect,
 * return JSON, validate input and get the logged-in user.
 */
abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/main'): Response
    {
        return Response::html(View::render($view, $data, $layout));
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect(url($path));
    }

    /** Redirect to the previous page (falls back to home). */
    protected function back(): Response
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
        return Response::redirect($referer);
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash('message', ['type' => $type, 'text' => $message]);
    }

    /**
     * Validates input. On failure, stores errors + old input in the session and
     * throws ValidationException, which Application turns into a redirect back.
     */
    protected function validate(Request $request, array $rules): array
    {
        $data = $request->all();
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('old', array_diff_key($data, array_flip(['password', 'password_confirmation', '_csrf'])));
            throw new ValidationException($validator->errors());
        }

        return array_intersect_key($data, $rules);
    }

    protected function user(): ?array
    {
        return Auth::user();
    }
}
