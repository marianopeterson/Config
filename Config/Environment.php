<?php

class Config_Environment
{
    /**
     * Takes an environment name and translates it into a sequence of
     * environments that form its ancestral lineage.
     * Example:
     *      given:      dev.foo
     *      returns:    array(default, dev, dev.foo)
     *
     * @param string $environment Environment name whose lineage will be returned.
     * @param array  $opts        Array of options affecting the lineage.
     *     Options include:
     *     - root:   String that will always be set as the root element.
     *     - prefix: String that is prepended to the name of each ancestor.
     *     - join:   String that is used to join the ancestor names together.
     *     - suffix: String that is appended to the name of each ancestor.
     *
     * @return array<string>
     */
    public function getLineage($environment, array $opts = array())
    {
        $defaultOpts = array(
                'root'   => null,
                'prefix' => '',
                'join'   => '.',
                'suffix' => '');
        // Order of args for array_diff_key() is important:
        $invalidOpts = array_diff_key($opts, $defaultOpts);
        if (!empty($invalidOpts)) {
            throw new Config_Exception('Invalid options: '
                    . implode(", ", array_keys($invalidOpts)));
        }
        // Order of args for array_merge() is important:
        $opts = array_merge($defaultOpts, $opts);
        extract($opts);

        $parents = '';
        $lineage = array();
        if ($root) {
            $lineage[] = $prefix . $root . $suffix;
        }
        foreach (explode(".", $environment) as $part) {
            $lineage[] = $prefix . $parents . $part . $suffix;
            $parents   .= $part . $join;
        }
        return $lineage;
    }
}
