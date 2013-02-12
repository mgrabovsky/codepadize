import sys, argparse
import requests

languages = [
	('C',          []),
	('C++',        ['cpp']),
	('D',          []),
	('Haskell',    ['hs']),
	('Lua',        []),
	('OCaml',      ['oc']),
	('PHP',        []),
	('Perl',       ['pl']),
	('Plain Text', ['text', 'txt']),
	('Python',     ['py']),
	('Ruby',       ['rb']),
	('Scheme',     ['scm']),
	('Tcl',        [])
]

parser = argparse.ArgumentParser(description='submits arbitrary chunk(s) of code to'
	' codepad.org')
parser.add_argument('files', metavar='file', nargs='*', type=argparse.FileType('r'),
	default=[sys.stdin], help='names of files to paste or - to read from STDIN')
parser.add_argument('-l', '--language', default='text',
	help='which syntax highlight to use (default: plain text)')
parser.add_argument('-p', '--private', action='store_true', default='',
	help='store as a private paste')
parser.add_argument('-r', '--run', action='store_true', default='',
	help='run the paste code after submitting')

args = parser.parse_args()

# Find what language to send to the server or default to plain text
language = args.language.lower()
for syn in languages:
	if language == syn[0].lower() or language in syn[1]:
		language = syn[0]
		break
else:
	language = 'Plain Text'

for f in args.files:
	payload = {
		'code': f.read(),
		'lang': language,
		'private': str(args.private),
		'run': str(args.run),
		'submit': 'Submit'
	}

	r = requests.post('http://codepad.org/', data=payload)
	print r.url
