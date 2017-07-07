<?php

namespace App\Http\Controllers\Auth;

use App\Events\LogEvent;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

/**
 * Class LoginController
 *
 * @author  Rob Attfield <emailme@robertattfield.com> <http://www.robertattfield.com>
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/faucets';

    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }


    /**
     * The user has been authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @param  mixed                   $user
     *
     * @return mixed
     */
    public function authenticated(Request $request, User $user)
    {
        if (empty($user)) {
            return redirect(route('login'));
        }
        $this->setRedirectedTo(route('users.panel', ['userSlug' => $user->slug]));

        flash('Welcome back ' . $user->first_name . "! Glad you have returned :).")->success();

        return redirect()->intended(route('users.panel', ['userSlug' => $user->slug]));
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        $user = User::withTrashed()->where('email', $request->get('email'))->first();

        if ($user->isDeleted()) {
            flash("Your account has been suspended/cancelled. Please contact
                  admin for further information, and possible account restoration.")->error();

            $this->logoutAuth();

            return redirect(route('login'));
        }

        return $this->guard()->attempt(
            $this->credentials($request), $request->has('remember')
        );
    }

    /**
     * Over-riding function to handle users logging out.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $this->logoutAuth();
        $this->clearSessions($request);

        return redirect(route('login'));
    }

    /**
     * Retrieve redirect path.
     *
     * @return string
     */
    protected function redirectTo()
    {
        return $this->redirectTo;
    }

    /**
     * Set the redirect path.
     *
     * @param $redirectTo
     */
    private function setRedirectedTo($redirectTo)
    {
        $this->redirectTo = $redirectTo;
    }
}
