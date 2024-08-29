<?php

namespace App\Http\Middleware;

use App\Models\Credential;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthMaru
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // authentication_code
        $authentication_code = $request->header('maru-authentication_code');

        if($authentication_code == "" || $authentication_code == null){
            return response()->json(["success" => false, "message" => "Authentication failed!"], 401);
        }

        // check if the authentication code is legit!
        $authenticated = Credential::where("authentication_code", $authentication_code)->first();

        if (!$authenticated) {
            return response()->json(["success" => false, "message" => "Authentication failed!"], 401);
        }

        // failed to authenticate
        // return response()->json(["success" => true, "message" => "Authentication success!"], 200);
        
        // check if auth_code is valid
        return $next($request);
    }
}
