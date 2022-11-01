<?php

namespace Peppers\Helpers\Types;

enum ResponseType {

    case File;
    case Html;
    case Json;
    case NoBody;
    case PlainText;
    case Redirect;
    case Xml;

}
