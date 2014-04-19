" -----------------------------------------------------------------------------
"
"   Sigma Query Database
"   ~~~~~~~~~~~~~~~~~~~~
"
"   Execute any query on the database and get the result onto the current
"   buffer.
"
"   0. Source this file :source %.
"   1. Open a new buffer.
"   2. Enter your query in the first line.
"   3. Press ';s'.
"   4. Buffer will be filled with the results.
"   5. Press 'u' and edit again.
"   6. Press ';s'.
"   7. Continue untill you get what you want.
"   8. Process with the Vim, Python or Unix tools.
"
"   Maintainer: Upeksha Wisidagama
"
" -----------------------------------------------------------------------------
"   Sample Queries
"
"   select id, token, eid, paid, seq_no from q2k_sigma_events where eid = 111 and paid = 'paid' order by seq_no desc
"   select id, token, eid, paid, seq_no from q2k_sigma_events where eid = 169 and paid = 'paid' order by seq_no desc
"   select id, token, eid, paid, seq_no from q2k_sigma_events where paid = 'paid' order by id desc
"   select medium, count(*) from q2k_sigma_events group by medium
"   select medium, count(*) from q2k_sigma_events where paid = 'paid' group by medium
"   select eid, medium, count(*) from q2k_sigma_events where paid = 'paid' group by eid, medium
"
"   2013-06-07 sequence number is 251 bug.
"   select id, fname, lname, token, reg_time, eid, ip, medium, paid, amount, seq_no FROM q2k_sigma_events WHERE ( eid ) IN ( 169 ) AND paid = 'paid' ORDER BY seq_no DESC LIMIT 50
"
"   'sigma_event_sequence' meta information.
"   select id, meta_value from q2k_posts join q2k_postmeta on q2k_posts.id = q2k_postmeta.post_id  where q2k_posts.post_type = 'events' and q2k_postmeta.meta_key = 'sigma_event_sequence'
"
"   get post titles.
"   select id, post_title from q2k_posts where post_type = 'events'
"   select id, post_title from q2k_posts where post_type = 'sequences'
"
"   select id, token, eid, paid, seq_no from q2k_sigma_events where eid = 111 and seq_no = 251 and id != 19 order by id asc
"
"   select count(*) from q2k_sigma_events where eid = 111 and seq_no = 251 and id != 19 order by id asc
"   select id, token, eid, paid, seq_no from q2k_sigma_events where eid = 111 order by seq_no desc limit 5
"
"   select ip, count(*) from q2k_sigma_events group by ip order by count(*) desc
"   select ip, count(*) from q2k_sigma_events group by ip
"   select id, fname, lname, bday, ip  from q2k_sigma_events where ip = '200.16.89.17'
"
"   select id, extra_items, count(*) from q2k_sigma_events where extra_items = '' or extra_items is null
"   select id, extra_items from q2k_sigma_events where (extra_items = '' or extra_items is null) and extra_items != 'none'
" -----------------------------------------------------------------------------
:fun! Sigma_Query_Database()
python << endpython
import vim
import MySQLdb as mdb

def sigma_query_database():
    current_buffer = vim.current.buffer
    sigma_query = current_buffer[0]
    data = sigma_query_db( sigma_query )
    sigma_write_buffer( data )

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

def sigma_write_buffer( data ):
    current_buffer = vim.current.buffer
    i = 0
    current_buff = []
    for datum in data :
        record = ''
        for field in datum :
            record = record + str(field) + '\t'

        current_buff.append(record)
        i = i + 1

    current_buffer[:] = current_buff
    vim.command( '%!column -t' )

sigma_query_database()
endpython
:endfun
" -----------------------------------------------------------------------------
:noremap <silent> ;s :call Sigma_Query_Database()<CR>
" -----------------------------------------------------------------------------
