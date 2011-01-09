<?php

class Config_Environment
{
    /**
     * Takes an environment name and translates it into a list of
     * ancestor environments.
     * Example:
     *      given:      dev.foo
     *      returns:    array(default, dev, dev.foo)
     *
     * @param string $env    Name of the environment whose ancestors will be determined
     * @param string $join   String used to join the ancestor names
     * @param string $prefix String that is prepended to each ancestor name
     * @param string $suffix String that is appended to each ancestor name
     *
     * @return array<string>
     */
    public function getAncestors($env, $join='.', $prefix='', $suffix='')
    {
        $base = '';
        $keys = array($prefix . 'default' . $suffix);
        foreach (explode(".", $env) as $part) {
            $keys[] = $prefix . $base . $part . $suffix;
            $base .= $part . $join;
        }
        return $keys;
    }
}

$env = new Config_Environment();
$keys = $env->getAncestors("dev.mariano.home", ".", "/Users/mariano/Code/gen/config/", ".ini");

$env = new Config_Environment("dev.mariano.home");
$keys = $env->getAncestors(
            "dev.mariano.home",
            ".",
            "/Users/mariano/Code/gen/config/",
            ".ini");
print_r($keys);

$keys = $env->getAncestors(
            "dev.mariano.home",
            "/",
            "/Users/mariano/Code/gen/config/",
            ".ini");
print_r($keys);

$keys = $env->getAncestors("dev.mariano.home");
print_r($keys);
