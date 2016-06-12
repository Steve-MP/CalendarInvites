<?php

namespace Bolt\Extension\SteveEMBO\CalendarInvites;

if (isset($app)) {
    $app['extensions']->register(new Extension($app));
}

