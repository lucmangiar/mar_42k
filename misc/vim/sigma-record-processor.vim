" -----------------------------------------------------------------------------
" Sigma Vim Utilities
"
" Maintainer: Upeksha Wisidagama
"
"   sql:
"   mysql -u uwroot -p -e"use sigmasec_wp747;
"   select id, token, eid, paid, seq_no from q2k_sigma_events
"   where eid = 111 and paid = 'paid' order by seq_no desc" > ~/sqlout/e111_assigned_full.log;
"
" -----------------------------------------------------------------------------
:fun! Sigma_Record_Processor()
python << endpython
import vim
import re

# Get the first sequence number
def get_first_seq_no():
    current_buffer = vim.current.buffer
    firstline = current_buffer[0]
    words = firstline.split()
    return words[4]

# Fill spaces in tokens and pad with *s
def sigma_formatter():
    # number of records to process
    current_buffer = vim.current.buffer
    total_records = len(vim.current.buffer)
    curline = vim.current.line
    firstno = get_first_seq_no()
    i = 0
    while i < total_records:
        curline = current_buffer[i]
        pattern = re.compile("(?P<id>\d{1,6})\t(?P<token>.*)\t(?P<event_id>\d{1,5})\t(?P<paid>paid)\t(?P<seq_no>\d.*)$")
        m = pattern.match(curline )
        words = m.groupdict()
        words = words['id']
        token = words['token']
        token = token.replace( ' ', '-' )
        token = '{:*^10}'.format(token);
        event_id = words['event_id']
        seq_no = words['seq_no']
        current_buffer[i] = words + '\t' + token + '\t' + event_id + '\t' + 'paid' + '\t' + seq_no
        i = i + 1

# Process file and find the missing sequence numbers
def sigma_processor():
    # number of records to process
    current_buffer = vim.current.buffer
    total_records = len(vim.current.buffer)
    curline = vim.current.line
    firstno = get_first_seq_no()
    i = 0
    while i < total_records:
        curline = current_buffer[i]
        words = curline.split()
        current_buffer[i] += '  >>  ' + str( int(words[4]) - int(firstno) -i )
        i = i + 1

sigma_formatter()
vim.command( "%!sort -n -k 5 " )
vim.command( "%!column -t " )
sigma_processor()
endpython
:endfun
" -----------------------------------------------------------------------------
:noremap <silent> ;s :call Sigma_Record_Processor()<CR>
" -----------------------------------------------------------------------------
