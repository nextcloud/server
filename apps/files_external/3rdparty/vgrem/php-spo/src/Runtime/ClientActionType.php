<?php

namespace Office365\PHP\Client\Runtime;

/**
 * Action type
 */
abstract class ClientActionType
{
    const CreateEntity = 2;
    const UpdateEntity = 4;
    const DeleteEntity = 8;
    const PostMethod = 16;
    const GetMethod = 32;
}