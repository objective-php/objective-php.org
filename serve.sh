#!/bin/sh

# default values

PORT=8080
APPLICATION_ENV="development"


pointer=1

while [[ $pointer -le $# ]]; do
   if [[ ${!pointer} != "-"* ]]; then ((pointer++)) # not a parameter flag so advance pointer
   else
      param=${!pointer}
      ((pointer_plus = pointer + 1))
      slice_len=1

      case $param in
         # paramater-flags with arguments
         -e|--environment) APPLICATION_ENV=${!pointer_plus}; ((slice_len++));;
         -p|--port) PORT=${!pointer_plus}; ((slice_len++));;


         # binary flags
         #-v|--quiet) quiet=true;;
         #        -d) debug=true;;
      esac

      # splice out pointer frame from positional list
      [[ $pointer -gt 1 ]] \
         && set -- $"{@:1:((pointer - 1))}" $"{@:((pointer + $slice_len)):$#}"\
         || set -- $"{@:((pointer + $slice_len)):$#}";
   fi
done

echo "\n"
echo "Listening to port: \033[32m$PORT\033[0m"
echo "Application environment: \033[32m$APPLICATION_ENV\033[0m \n\n"

env APPLICATION_ENV=${APPLICATION_ENV} php -S localhost:${PORT} -t public
