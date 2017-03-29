<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash as LaracastsFlash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Prettus\Repository\Criteria\RequestCriteria;

class UserController extends AppBaseController
{
    /** @var  UserRepository */
    private $userRepository;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepository = $userRepo;
        $this->middleware('auth', ['except' => ['index', 'show']]);
    }

    /**
     * Display a listing of the User.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $this->userRepository->pushCriteria(new RequestCriteria($request));
        $users = null;
        if(Auth::guest() || Auth::user()->hasRole('user') &&
            (!Auth::user()->hasRole('owner') && !Auth::user()->hasRole('administrator'))){
            $users = $this->userRepository->all();
        }
        else{
            $users = $this->userRepository->withTrashed()->get();
        }

        return view('users.index')
            ->with('users', $users);
    }

    /**
     * Show the form for creating a new User.
     *
     * @return Response
     */
    public function create()
    {

        $user = null;
        if(Auth::user()->hasRole('owner'))
        {
            return view('users.create')->with('user');
        } else{
            abort(403);
        }
    }

    /**
     * Store a newly created User in storage.
     *
     * @param CreateUserRequest $request
     *
     * @return Response
     */
    public function store(CreateUserRequest $request)
    {
        if(Auth::user()->hasRole('owner')) {
            $input = $request->all();

            $user = $this->userRepository->create($input);

            LaracastsFlash::success('User saved successfully.');

            return redirect(route('users.index'));
        } else{
            abort(403);
        }
    }

    /**
     * Display the specified User.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function show($slug)
    {
        $user = $this->userRepository->findByField('slug', $slug)->first();
        $message = null;

        if(Auth::guest() && !empty($user) && $user->isDeleted()){ // If the visitor is a guest, user exists, and user is soft-deleted
            LaracastsFlash::error('User not found');
            return redirect(route('users.index'));
        }
        else if(
            !Auth::guest() && // If the visitor isn't a guest visitor,
            Auth::user()->hasRole('user') && // If the visitor is an authenticated user with 'user' role
            !Auth::user()->hasRole('owner') && // If the visitor is an authenticated user, but without 'owner' role,
            $user->isDeleted() // If the requested user has been soft-deleted
        ){
            LaracastsFlash::error('User not found');
            return redirect(route('users.index'));
        }
        else{
            if(
                !empty($user) && // If the user exists,
                $user->isDeleted() && // If the user is soft-deleted,
                Auth::user()->hasRole('owner') // If the currently authenticated user has 'owner' role,
            ){
                if(Auth::user()->hasRole(['owner'])){
                    $message = 'The user has been temporarily deleted. You can restore the user or permanently delete them.';
                }

                return view('users.show')
                    ->with('user', $user)
                    ->with('message', $message);
            }
            if(!empty($user) && !$user->isDeleted()){ // If the user exists and isn't soft-deleted

                return view('users.show')
                    ->with('user', $user)
                    ->with('message', $message);
            } else{
                LaracastsFlash::error('User not found');
                return redirect(route('users.index'));
            }
        }
    }

    /**
     * Show the form for editing the specified User.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function edit($slug)
    {
        $user = $this->userRepository->findByField('slug', $slug)->first();
        if($user == Auth::user() || Auth::user()->hasRole('owner'))
        {
            $user = $this->userRepository->findByField('slug', $slug)->first();
            if (empty($user)) {
                LaracastsFlash::error('User not found');

                return redirect(route('users.index'));
            }

            return view('users.edit')
                ->with('user', $user)
                ->with('slug', $slug);
        } else {
            abort(403);
        }
    }

    /**
     * Update the specified User in storage.
     *
     * @param  int              $id
     * @param UpdateUserRequest $request
     *
     * @return Response
     */
    public function update($slug, UpdateUserRequest $request)
    {
        $user = $this->userRepository->findByField('slug', $slug)->first();
        Functions::userCanAccessArea(
            Auth::user(),
            'users.update',
            null,
            [
                'user' => $user,
                'slug' => $slug
            ]
        );

        if (empty($user)) {
            LaracastsFlash::error('User not found');

            return redirect(route('users.index'));
        }

        $updateRequestData = $request->has('password') &&
            $request->has('password_confirmation') ?
            $request->all() :
            $request->except(['password','password_confirmation']);

        $user = $this->userRepository->update(
            $updateRequestData,
            $user->slug
        );

        LaracastsFlash::success('User updated successfully.');

        return redirect(route('users.index'));
    }

    /**
     * Remove the specified User from storage.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function destroy($slug)
    {
        $user = $this->userRepository->findByField('slug', $slug)->first();
        Functions::userCanAccessArea(
            Auth::user(),
            'users.destroy',
            null,
            [
                'user' => $user,
                'slug' => $slug
            ]
        );

        if (empty($user)) {
            LaracastsFlash::error('User not found');

            return redirect(route('users.index'));
        }

        if($user->hasRole('owner') == true){
            LaracastsFlash::error('An owner-user cannot be deleted.');

            return redirect(route('users.index'));
        }

        $this->userRepository->deleteWhere(['slug' => $slug]);

        LaracastsFlash::success('User deleted successfully.');

        return redirect(route('users.index'));
    }

    public function destroyPermanently($slug)
    {
        $user = $this->userRepository->findByField('slug', $slug)->first();
        Functions::userCanAccessArea(
            Auth::user(),
            'users.delete-permanently',
            null,
            [
                'user' => $user,
                'slug' => $slug
            ]
        );

        if (empty($user)) {
            LaracastsFlash::error('User not found');

            return redirect(route('users.index'));
        }

        $this->userRepository->deleteWhere(['slug' => $slug], true);

        LaracastsFlash::success('User was permanently deleted!');

        return redirect(route('users.index'));

    }

    public function restoreDeleted($slug){
        $user = $this->userRepository->findByField('slug', $slug)->first();
        Functions::userCanAccessArea(
            Auth::user(),
            'users.restore',
            null,
            [
                'user' => $user,
                'slug' => $slug
            ]
        );

        if (empty($user)) {
            LaracastsFlash::error('User not found');

            return redirect(route('users.index'));
        }

        $this->userRepository->restoreDeleted($slug);

        LaracastsFlash::success('User was successfully restored!');

        return redirect(route('users.index'));

    }
}
