function zeroFill( number, width )
{
  width -= number.toString().length;
  if ( width > 0 )
  {
    return new Array( width + (/\./.test( number ) ? 2 : 1) ).join( '0' ) + number;
  }
  return number;
}

function stringEndsWith(string, suffix) {
    return string.indexOf(suffix, string.length - suffix.length) !== -1;
};
