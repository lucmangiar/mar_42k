:function! Sigma_SQL_Utility()

    " Event IDs to operate
    :let s:Num_42K_2013 = 111
    :let s:Num_21K_2013 = 169

    " Assuming cursor is on <table>
    " Copy token to register t
    " t > token
    :execute "normal /token\<cr>f>lviw\"ty"

    " Copy registration date time to register d
    " d > datetime
    :execute "normal /reg_time\<cr>f>lvf<h\"dy"

    " Copy event id to register e
    " e > event
    :execute "normal /eid\<cr>f>lviw\"ey"

    " Copy payment status to register p
    " p > paymentstatus
    :execute "normal /paid\<cr>f>lviw\"py"

    " Pre string composition
    " r > string prefix
    :let @r = @d . ' \| ' . @t . ' \| ' . @e . ' \| ' . @p

    " Branch According to event
    :if( s:Num_42K_2013 == @e && 'paid' == @p )
        :let seq_no = g:Num_42K_2013_Index
        :let g:Num_42K_2013_Index += 1
        :execute "silent !echo " . @r . ' \| ' . seq_no . " >> misc/Num_42K_2013_Sequence.log"
    :elseif( s:Num_21K_2013 == @e && 'paid' == @p )
        :let seq_no = g:Num_21K_2013_Index
        :let g:Num_21K_2013_Index += 1
        :execute "silent !echo " . @r . ' \| ' . seq_no . " >> misc/Num_21K_2013_Sequence.log"
    :else
        :let seq_no = 'none'
        :execute "silent !echo " . @r . ' \| ' . seq_no . " >> misc/Other.log"
    :endif

    " Store new sequence number in register s
    " s > sequence(new)
    let @s = '            <column name="seq_no">' . seq_no . '</column>'

    " Delete the current sequence
    :execute "normal /seq_no\<cr>dd"

    " Insert the new sequence
    :execute "normal O\<Esc>\"sP"

:endfunction

:function! Sigma_Reset()
    :let g:Num_42K_2013_Index = 251
    :let g:Num_21K_2013_Index = 201

    :execute "silent !echo '' > misc/Num_42K_2013_Sequence.log"
    :execute "silent !echo '' > misc/Num_21K_2013_Sequence.log"
    :execute "silent !echo '' > misc/Other.log"
:endfunction

:nnoremap <silent> ;s :call Sigma_SQL_Utility()<CR>
:nnoremap <silent> ;z :call Sigma_Reset()<CR>
