Bookmark Component
==================

A PHP Component for parsing [Netscape Bookmark File Format](http://msdn.microsoft.com/en-us/library/aa753582\(v=vs.85\).aspx).

It is inspired by the [Markio](https://github.com/spajus/markio) project.

    use Squarify\Component\Bookmark\Bookmark;

    $array = Bookmark::parse(file_get_contents($filename));

    print_r($array);

