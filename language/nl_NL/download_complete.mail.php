onderwerp: Download voltooid

{alternative:plain}

Geachte mevouw, heer,,

Uw download van onderstaande {if:files>1}bestanden{else}bestand{endif} is voltooid : 

{if:files>1}{each:files as file}
  - {file.name} ({size:file.size})
{endeach}{else}
{files.first().name} ({size:files.first().size})
{endif}

Hoogachtend,
{cfg:site_name}

{alternative:html}

<p>
   Gecachte mevrouw, heer,
</p>

<p>
    Uw download van onderstaande {if:files>1}bestanden{else}bestand{endif} is voltooid : </p>

<p>
    {if:files>1}
    <ul> {each:files as file}
                <li>{file.name} ({size:file.size})</li>
            {endeach}
    </ul>
    {else}
    {files.first().name} ({size:files.first().size})
    {endif}
</p>

<p>
    Hoogachtend,<br />
    {cfg:site_name}
</p>