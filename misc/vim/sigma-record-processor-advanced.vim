"
"   Sigma Utilities
"
"   Maintainer: Upeksha Wisidagama
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
import os
import MySQLdb as mdb
import sys

def sigma_prepare_raw_buffer_1():
    sigma_query = 'select id, token, eid, paid, seq_no from q2k_sigma_events' + \
    ' where eid = 111 and paid = \'paid\' order by seq_no desc '
    data = sigma_query_db( sigma_query )
    vim.command('vsp Sigma_111' )
    sigma_write_buffer( data, 'Sigma_111' )
    sigma_init('Sigma_111')

def sigma_prepare_raw_buffer_2():
    sigma_query = 'select id, token, eid, paid, seq_no from q2k_sigma_events' + \
    ' where eid = 169 and paid = \'paid\' order by seq_no desc '
    data = sigma_query_db( sigma_query )
    vim.command('sp Sigma_169' )
    sigma_write_buffer( data, 'Sigma_169' )
    sigma_init('Sigma_169')

def sigma_query_db( sigma_query ):
    try:
        con = mdb.connect('localhost', 'root', 'aaa', 'sigmasec_wp747');
        cur = con.cursor()
        cur.execute(sigma_query)
        data = cur.fetchall()
        return data
    except mdb.Error, e:
        print "Error %d: %s" % (e.args[0],e.args[1])
        sys.exit(1)
    finally:
        if con:
            con.close()

# Get the first sequence number
def sigma_get_first_seq_no( target_buffer ):
    current_buffer = sigma_get_buffer_by_name( target_buffer )
    firstline = current_buffer[0]
    words = firstline.split()
    return words[4]

# Fill spaces in tokens and pad with *s
def sigma_formatter( target_buffer ):
    # number of records to process
    current_buffer = sigma_get_buffer_by_name( target_buffer )
    total_records = len( current_buffer )
    firstno = sigma_get_first_seq_no( target_buffer )
    i = 0
    while i < total_records:
        curline = current_buffer[i]
        pattern = re.compile("(?P<id>\d{1,6})\t(?P<token>.*)\t(?P<event_id>\d{1,5})\t(?P<paid>paid)\t(?P<seq_no>\d.*)$")
        m = pattern.match(curline )
        words = m.groupdict()
        rid = words['id']
        token = words['token']
        token = token.replace( ' ', '-' )
        token = '{:*^10}'.format(token);
        event_id = words['event_id']
        seq_no = words['seq_no']
        current_buffer[i] = rid + '\t' + token + '\t' + event_id + '\t' + 'paid' + '\t' + seq_no
        i = i + 1

# Process file and find the missing sequence numbers
def sigma_processor( target_buffer ):
    # number of records to process
    current_buffer = sigma_get_buffer_by_name( target_buffer )
    total_records = len(vim.current.buffer)
    firstno = sigma_get_first_seq_no( target_buffer )
    i = 0
    while i < total_records:
        curline = current_buffer[i]
        words = curline.split()
        current_buffer[i] += '  >>  ' + str( int(words[4]) - int(firstno) -i )
        i = i + 1

def sigma_write_buffer( data, target_buffer ):
    current_buffer = sigma_get_buffer_by_name( target_buffer )
    i = 0
    current_buff = []
    for datum in data :
        record = ''
        for field in datum :
            record = record + str(field) + '\t'

        current_buff.append(record)
        i = i + 1

    current_buffer[:] = current_buff

def sigma_get_buffer_by_name( buffer_name ):
    cwd = os.getcwd()
    requested_buffer = cwd + '/' + buffer_name
    print requested_buffer
    for b in vim.buffers:
        if requested_buffer == b.name:
            return b

def sigma_prepare():
    sigma_prepare_raw_buffer_1()
    sigma_prepare_raw_buffer_2()

def sigma_init( curbuffer ):
    sigma_formatter(curbuffer)
    vim.command( "%!sort -n -k 5 " )
    vim.command( "%!column -t " )
    sigma_processor(curbuffer)

sigma_prepare()
endpython
:endfun

:fun! Sigma_Windows()
python << endpython
import vim
import os

def sigma_print( data ):
    current_buffer = vim.current.buffer
    current_line = current_buffer[:].index(vim.current.line)
    current_buffer.append( data, current_line )

def sigma_write_buffer( data, target_buffer ):
    current_buffer = sigma_get_buffer_by_name( target_buffer )
    current_buffer[:] = data

def sigma_get_buffer_by_name( buffer_name ):
    cwd = os.getcwd()
    requested_buffer = cwd + '/' + buffer_name
    print requested_buffer
    for b in vim.buffers:
        if requested_buffer == b.name :
            return b

    return false

vim.command('vsp Sigma_Buffer' )
filedata = ['hello', 'world!' ]
sigma_write_buffer( filedata, 'Sigma_Buffer' )

endpython
:endfun
" -----------------------------------------------------------------------------
:noremap <silent> ;s :call Sigma_Record_Processor()<CR>
:noremap <silent> ;a :call Sigma_Windows()<CR>
" -----------------------------------------------------------------------------
