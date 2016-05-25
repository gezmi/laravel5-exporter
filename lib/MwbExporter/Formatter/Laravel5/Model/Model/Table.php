<?php

/*
 * The MIT License
 *
 * Copyright (c) 2016 Mateus Vitali <mateus.c.vitali@gmail.com>
 * Copyright (c) 2012-2014 Toha <tohenk@yahoo.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace MwbExporter\Formatter\Laravel5\Model\Model;

use MwbExporter\Model\Table as BaseTable;
use MwbExporter\Formatter\Laravel5\Model\Formatter;
use MwbExporter\Writer\WriterInterface;
use MwbExporter\Helper\Comment;

class Table extends BaseTable
{
    public function getNamespace()
    {
        return $this->translateVars($this->getConfig()->get(Formatter::CFG_NAMESPACE));
    }

    public function getParentTable()
    {
        return $this->translateVars($this->getConfig()->get(Formatter::CFG_PARENT_TABLE));
    }

    public function writeTable(WriterInterface $writer)
    {
        if (!$this->isExternal()) {
            // $this->getModelName() return singular form with correct camel case
            // $this->getRawTableName() return original form with no camel case
            $writer
                ->open($this->getTableFileName())
                ->write('<?php namespace ' . $this->getNamespace() . ';')
                ->write('')
                ->write('use Illuminate\Database\Eloquent\Model;')
                ->write('')
                ->writeCallback(function(WriterInterface $writer, Table $_this = null) {
                    if ($_this->getConfig()->get(Formatter::CFG_ADD_COMMENT)) {
                        $writer
                            ->write($_this->getFormatter()->getComment(Comment::FORMAT_PHP))
                            ->write('')
                        ;
                    }
                })
                ->write('class ' . $this->getModelName() . ' extends '. $this->getParentTable())
                ->write('{')
                ->indent()
                    ->write('/**')
                    ->write(' * The database table used by the model.')
                    ->write(' * ')
                    ->write(' * @var string')
                    ->write(' */')
                    ->write('protected $table = \''. $this->getRawTableName() .'\';')
                    ->write('')                 
                    ->writeCallback(function(WriterInterface $writer, Table $_this = null) {
                        if ($_this->getConfig()->get(Formatter::CFG_GENERATE_FILLABLE)) {
                            $_this->writeFillable($writer);
                        }
                    })
                ->outdent()
                ->write('}')
                ->write('')
                ->close()
            ;

            return self::WRITE_OK;
        }

        return self::WRITE_EXTERNAL;
    }

    public function writeFillable(WriterInterface $writer)
    {
        /*
         * FIXME: identify which columns are FK and not add to the array fillable
         */
        $writer
            ->write('/**')
            ->write(' * The attributes that are mass assignable.')
            ->write(' * ')
            ->write(' * @var array')
            ->write(' */')   
            ->writeCallback(function(WriterInterface $writer, Table $_this = null) {
                if (count($_this->getColumns())) {
                    $content = '';
                    $columns = $_this->getColumns();
                    foreach ($columns as $column) {
                        $content .= '\'' . $column->getColumnName() . '\',';
                    }
                    $writer->write('protected $fillable = [' . substr($content, 0, -1) . '];');
                } 
            })
        ;

        return $this;
    }
}