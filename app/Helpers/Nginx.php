<?php

namespace App\Helpers;

class Nginx
{
    /**
     * Modify the Nginx config to restrict access to the site except for the allowed IPs.
     */
    public static function setAllowedIps(string $currentNginxConfig, string $serverIp): string
    {
        $allowedIps = str(config('forge.allowed_ips'))
            ->explode(';')
            ->filter()
            ->map(fn ($command) => 'allow '.str($command)->trim()->value().';')
            ->prepend("allow $serverIp;")
            ->push("deny all;\n\n")
            ->prepend('# IP restrictions (via ALLOWED_IPS)')
            ->join("\n");

        return str($currentNginxConfig)
            ->replace("location / {\n", "location / {\n".$allowedIps)
            ->value();
    }
}
