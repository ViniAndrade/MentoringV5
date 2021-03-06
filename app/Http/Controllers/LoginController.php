<?php

namespace Mentor\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mentor\Models\User;
use Mentor\Repositories\UserRepositoryEloquent;

class LoginController extends Controller
{

    /**
     * @var UserRepositoryEloquent
     */
    private $eloquent;

    public function __construct(User $eloquent)
    {
        $this->eloquent = $eloquent;
    }

    public function index()
    {
        return view('auth.login');
    }

    public function auth(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, true)):
            return redirect()->route('app.index');
        endif;
        $request->session()->flash('error', 'Login e/ou senha inválido');
        return redirect()->back();
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->route('login.index');
    }

    public function register()
    {
        return view('auth.register');
    }

    public function reset()
    {
        return view('auth.passwords.email');
    }

    public function resetPassword()
    {
        return view('auth.passwords.resetPassword');
    }

    public function newPassword(Request $request)
    {
        $user = $this->eloquent->where('email', $request['email'])->get();
        if($user):
            return view('auth.passwords.resetPassword', compact('user'));
        else:
            $request->session()->flash('error', 'E-mail não existe');
            return redirect()->back();
        endif;
    }

    public function newPasswordReset(Request $request, $id)
    {
        if($request['password'] === $request['rPassword']):
            $user = $this->eloquent->find($id);
            $user->password = bcrypt($request['password']);
            $user->save();
            return redirect()->route('login.index');
        endif;
    }

    public function create(Request $request){
        try {
            $dadosUsuario = $request->all();
            // Hash está ultrapassado, bcrypt é melhor.
            $dadosUsuario['password'] = bcrypt($dadosUsuario['password']);
            // Sempre usar instancias....
            $this->eloquent->create($dadosUsuario);

            return redirect()->route('login.index');

        } catch (QueryException $e) {

            $error = strtolower($e->getMessage());
            $usuarioDuplicado = strpos($error, 'integrity constraint violation');

            // Por padrão do laravel, é costume a galera usar bloco : endif em methods
            // Correção
            if(!$usuarioDuplicado):
                $request->session()->flash('error', 'Ocorreu um erro.');
            else:
                $request->session()->flash('error', 'Usuário já cadastrado.');
            endif;

            return view('auth.register');
        }
    }
}
