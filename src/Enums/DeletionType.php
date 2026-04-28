<?php

namespace Rarq\FilamentQuickNotes\Enums;

enum DeletionType: string
{
    case SOFT = 'soft';
    case FORCE = 'force';
}